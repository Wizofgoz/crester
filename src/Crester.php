<?php
namespace Crester;
use \Crester\Exceptions;
use \Crester\Cache\Cache;
use \Crester\Core\CREST;
use \Crester\Core\RateLimiter;
use \Crester\Core\XML;
class Crester
{
	const AUTH_URL = 'https://login.eveonline.com/oauth/authorize/';
	
	protected static $shared = array();
	
	protected static $parameters = array();
	
	protected static $configs = array();
	/*
	*	
	*/
	public function __construct()
	{
		
	}
	
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
	}
	
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
		self::$parameters['auth_code'] = $AuthCode;
		$crest = $this->crest();
		self::$parameters['token'] = $crest->getToken();
		self::$parameters['expiration'] = $crest->getExpiration();
		self::$parameters['refresh'] = $crest->getRefreshToken();
		return $crest;
	}
	
	public function fromRefreshToken($refresh_token)
	{
		self::$parameters['refresh'] = $refresh_token;
		return $this->crest(true);
	}
	
	public function crest($refresh = false)
	{
		if(!isset(self::$shared['crest']))
		{
			$core_config = $this->getCoreConfig();
			$limiter = $this->limiter();
			$cache = $this->cache();
			return self::$shared['crest'] = new CREST($core_config['client_id'], $core_config['secret_key'], ($refresh === false ? self::$parameters['auth_code'] : self::$parameters['refresh']), $limiter, $cache, $refresh);
		}
		return self::$shared['crest'];
	}
	
	public function xml()
	{
		if(!isset(self::$shared['xml']))
		{
			$cache = $this->cache();
			$core_config = $this->getCoreConfig();
			return self::$shared['xml'] = new XML(self::$parameters['token'], $core_config['user_agent'], $cache);
		}
		return self::$shared['xml'];
	}
	
	protected function cache()
	{
		if(!isset(self::$shared['cache']))
		{
			$core_config = $this->getCacheConfig();
			return self::$shared['cache'] = new Cache($core_config['enabled'], $this->getCacheDriver(), $core_config['default_length']);
		}
		return self::$shared['cache'];
	}
	
	protected function limiter()
	{
		if(!isset(self::$shared['limiter']))
		{
			$core_config = $this->getCoreConfig();
			return self::$shared['limiter'] = new RateLimiter($core_config['limiter']['limit'], $core_config['limiter']['frequency']);
		}
		return self::$shared['limiter'];
	}
	
	protected function getCoreConfig()
	{
		if(!isset(self::$configs['core']))
		{
			return self::$shared['core'] = require(__DIR__.'/Config/CREST.php');
		}
		return self::$shared['core'];
	}
	
	protected function getCacheConfig()
	{
		if(!isset(self::$configs['cache']))
		{
			return self::$shared['cache'] = require(__DIR__.'/Config/Cache.php');
		}
		return self::$shared['cache'];
	}

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
