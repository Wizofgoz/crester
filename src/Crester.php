<?php
/*
*	MIT License
*
*	Copyright (c) 2016 Wizofgoz
*
*	Permission is hereby granted, free of charge, to any person obtaining a copy
*	of this software and associated documentation files (the "Software"), to deal
*	in the Software without restriction, including without limitation the rights
*	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
*	copies of the Software, and to permit persons to whom the Software is
*	furnished to do so, subject to the following conditions:
*
*	The above copyright notice and this permission notice shall be included in all
*	copies or substantial portions of the Software.
*
*	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
*	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
*	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
*	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
*	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
*	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
*	SOFTWARE.
*/
namespace Crester;
use \Crester\Exceptions;
use \Crester\Cache\Cache;
use \Crester\Core\CREST;
use \Crester\Core\RateLimiter;
use \Crester\Core\XML;
class Crester
{
	/*
	*	URL to redirect to for SSO
	*
	*	@var string
	*/
	const AUTH_URL = 'https://login.eveonline.com/oauth/authorize/';
	
	/*
	*	Array of component singletons
	*
	*	@var array
	*/
	protected static $shared = array();
	
	/*
	*	Array of parameters to be shared between components
	*
	*	@var array
	*/
	protected static $parameters = array();
	
	/*
	*	Array of configs loaded
	*
	*	@var array
	*/
	protected static $configs = array();

	/*
	*	Initialize the Object
	*
	*	@return void	
	*/
	public function __construct(){}
	
	/*
	*	Redirect to the Eve Online SSO to obtain authorization
	*
	*	@return void
	*/
	public function redirect()
	{
		$core_config = $this->getCoreConfig();
		$state_str = "";
		if($core_config['check_state'])
		{
			$state = \urlencode(\hash("sha256", \time()));
			$_SESSION['sso_state'] = $state;
			$state_str = "&state=$state";
		}
		header("Location: ".self::AUTH_URL."?response_type=code&redirect_uri=".$core_config['callback_url']."&client_id=".$core_config['client_id']."&scope=".implode(" ", $core_config['scopes']).$state_str);
		exit;
	}
	
	/*
	*	Handle the callback from the SSO and initialize the CREST connection
	*
	*	@param string $AuthCode
	*	@param string $State
	*
	*	@return \Crester\Core\CREST
	*
	*	@throws \Exception
	*/
	public function handleCallback($AuthCode, $State = '')
	{
		$core_config = $this->getCoreConfig();
		if($core_config['check_state'])
		{
			if(!isset($_SESSION['sso_state']))
				throw new \Exception('State not found in session');
			if($_SESSION['sso_state'] !== $State)
				throw new \Exception('States do not match');
		}
		return $this->crest(false, $AuthCode);
	}
	
	/*
	*	Refresh the CREST connection with a previous Refresh Token
	*
	*	@param string $refresh_token
	*
	*	@return \Crester\Core\CREST
	*/
	public function fromRefreshToken($refresh_token)
	{
		return $this->crest(true, $refresh_token);
	}
	
	/*
	*	Get an instance of the CREST connection
	*
	*	@param boolean $refresh
	*	@param string $token
	*
	*	@return \Crester\Core\CREST
	*/
	public function crest($refresh = false, $token = null)
	{
		if(!isset(self::$shared['crest']))
		{
			$core_config = $this->getCoreConfig();
			$limiter = $this->limiter();
			$cache = $this->cache();
			return self::$shared['crest'] = new CREST($core_config, $token, $limiter, $cache, $refresh);
		}
		return self::$shared['crest'];
	}
	
	/*
	*	Get an instance of the XML connection
	*
	*	@return \Crester\Core\XML
	*/
	public function xml()
	{
		if(!isset(self::$shared['xml']))
		{
			$cache = $this->cache();
			$core_config = $this->getCoreConfig();
			$xml = new XML($core_config, $cache);
			if($core_config['xmlAPI']['CrestAuth'])
			{
				$this->crest()->attach($xml);
				$this->crest()->notify();
			}
			return self::$shared['xml'] = $xml;
		}
		return self::$shared['xml'];
	}

	/*
	*	Get an instance of the Cache handler
	*
	*	@return \Crester\Cache\Cache
	*/
	protected function cache()
	{
		if(!isset(self::$shared['cache']))
		{
			$cache_config = $this->getCacheConfig();
			return self::$shared['cache'] = new Cache($cache_config['enabled'], $this->getCacheDriver(), $cache_config['default_length']);
		}
		return self::$shared['cache'];
	}
	
	/*
	*	Get an instance of the Rate Limiter
	*
	*	@return \Crester\Core\RateLimiter
	*/
	protected function limiter()
	{
		if(!isset(self::$shared['limiter']))
		{
			$core_config = $this->getCoreConfig();
			return self::$shared['limiter'] = new RateLimiter($core_config['limiter']['limit'], $core_config['limiter']['frequency']);
		}
		return self::$shared['limiter'];
	}
	
	/*
	*	Get an instance of the core config file
	*
	*	@return array
	*/
	protected function getCoreConfig()
	{
		if(!isset(self::$configs['core']))
		{
			return self::$shared['core'] = require(__DIR__.'/Config/CREST.php');
		}
		return self::$shared['core'];
	}
	
	/*
	*	Get an instance of the cache config file
	*
	*	@return array
	*/
	protected function getCacheConfig()
	{
		if(!isset(self::$configs['cache']))
		{
			return self::$shared['cache'] = require(__DIR__.'/Config/Cache.php');
		}
		return self::$shared['cache'];
	}

	/*
	*	Get an instance of the cache driver
	*
	*	@return \Crester\Cache\CacheInterface
	*
	*	@throws \Exception
	*/
	protected function getCacheDriver()
	{
		$cache_config = $this->getCacheConfig();
		if(!$cache_config['enabled'])
			return NULL;
		switch($cache_config['driver'])
		{
			case 'database':
				return new \Crester\Cache\DBHandler($cache_config['database']);
				break;
			case 'redis':
				// disabled
			default:
				throw new \Exception('Unknown cache driver specified in config');
		}
	}
}
?>
