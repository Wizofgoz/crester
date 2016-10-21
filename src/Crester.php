<?php
namespace Crester;
use \Crester\Exceptions;
class Crester
{
	const AUTH_URL = 'https://login.eveonline.com/oauth/authorize/';
	
	protected static $shared = array();
	
	protected static $parameters = array();
	/*
	*	Constructor - Takes Authorization Code from Eve SSO
	*/
	public function __construct($AuthCode, $State)
	{
		if(!isset($_SESSION['sso_state']))
			throw new \Exception('State not found in session');
		if($_SESSION['sso_state'] !== $State)
			throw new \Exception('States do not match');
		self::$parameters['auth_code'] = $AuthCode;
		$crest = $this->crest();
		self::$parameters['token'] = $crest->getToken();
		self::$parameters['expiration'] = $crest->getTokenExpiration();
	}
	
	public static function redirect()
	{
		$core_config = require(__DIR__.'/Config/CREST.php');
		$state = url_encode(hash("sha256", time()));
		$_SESSION['sso_state'] = $state
		header("Location: ".self::AUTH_URL."?response_type=code&redirect_uri=".$core_config['callback_url']."&client_id=".$core_config['client_id']."&scope=".implode(" ", $core_config['scopes'])."&state=".$state);
	}
	
	public function crest()
	{
		if(!isset(self::$shared['crest']))
		{
			$core_config = require(__DIR__.'/Config/CREST.php');
			$limiter = $this->limiter();
			$cache = $this->cache();
			return self::$shared['crest'] = new CREST($core_config['client_id'], $core_config['secret_key'], self::$parameters['auth_code'], $limiter, $cache);
		}
		return self::$shared['crest'];
	}
	
	public function xml()
	{
		if(!isset(self::$shared['xml']))
		{
			$cache = $this->cache();
			return self::$shared['xml'] = new XML(self::$parameters['token'], $cache);
		}
		return self::$shared['xml'];
	}
	
	protected function cache()
	{
		if(!isset(self::$shared['cache']))
		{
			$core_config = require(__DIR__.'/Config/Cache.php');
			return self::$shared['cache'] = new Cache($core_config['driver'], $core_config['enabled'], $core_config['default_length'], $core_config[$core_config['driver']]);
		}
		return self::$shared['cache'];
	}
	
	protected function limiter()
	{
		if(!isset(self::$shared['limiter']))
		{
			$core_config = require(__DIR__.'/Config/CREST.php');
			return self::$shared['limiter'] = new RateLimiter($core_config['limiter']['limit'], $core_config['limiter']['frequency']);
		}
		return self::$shared['limiter'];
	}
}
?>
