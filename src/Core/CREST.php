<?php
namespace Crester\Core;
use \Crester\Core\CRESTBase;
use \Crester\Exceptions\CRESTAPIException;
class CREST extends CRESTBase
{
	/*
	*	Code retrieved from SSO
	*
	*	@var string
	*/
	protected $Authorization_Code;

	/*
	*	Token used to authenticate CREST calls
	*
	*	@var string
	*/
	protected $Access_Token;

	/*
	*	Whether the authorization code has been verified
	*
	*	@var boolean
	*/
	protected $Verified_Code = false;

	/*
	*	Route for API calls to follow
	*
	*	@var \SPLQueue
	*/
	protected $APIRoute;

	/*
	*	Route used so far in call stack
	*
	*	@var string
	*/
	protected $UsedRoute;

	/*
	*	Rate Limiter class makes sure API calls don't exceed limits
	*
	*	@var \Crester\Core\RateLimiter
	*/
	protected $RateLimiter;

	/*
	*	Cache controller
	*
	*	@var \Crester\Cache\Cache
	*/
	protected $Cache;

	/*
	*	Token used to get a fresh access token
	*
	*	@var string
	*/
	protected $RefreshToken;

	/*
	*	When the current access token needs to be refreshed
	*
	*	@var integer
	*/
	protected $RefreshTime;

	/*
	*	ClientID for Eve Server Authentication
	*
	*	@var string
	*/
	protected $client_id;

	/*
	*	SecretKey for Eve Server Authentication
	*
	*	@var string
	*/
	protected $secret_key;

	/*
	*	User Agent to use when communicating with API
	*
	*	@var string
	*/
	protected $user_agent;

	/*
	*	Whether the connection requires authorization
	*
	*	@var boolean
	*/
	protected $authorize;
	
	/*
	*	Constructor
	*
	*	@param array $config
	*	@param null|string
	*	@param \Crester\Core\RateLimiter $limiter
	*	@param \Crester\Cache\Cache $cache
	*	@param boolean $refresh
	*
	*	@return void
	*/
	public function __construct(array $config, $code, RateLimiter $limiter, \Crester\Cache\Cache $cache, $refresh = false)
	{
		$this->client_id = $config['client_id'];
		$this->secret_key = $config['secret_key'];
		$this->user_agent = (isset($config['user_agent']) ? $config['user_agent'] : 'CRESTer');
		$this->authorize = (isset($config['authorize']) ? $config['authorize'] : false);
		$this->RateLimiter = $limiter;
		$this->Cache = $cache;
		// if connection requires authorization
		if($this->authorize)
		{
			// if connection is not to be built from a refresh token (new authorization)
			if($refresh !== true)
			{
				$this->setAuthCode($code);
			}
			// if connection is to be refreshed from previous session
			else
			{
				$this->RefreshTime = \time() - 3600;	//	time in the past so refresh goes through
				$this->refresh();
			}
		}
	}
	
	/*
	*	Set the current Authorization Code for the connection
	*
	*	@param string $Code
	*
	*	@return void
	*/
	public function setAuthCode($Code)
	{
		$this->Authorization_Code = $Code;
		try{
			$this->verifyCode();
		}catch(CRESTAPIException $e){
			echo $e;
			exit;
		}
	}
	
	/*
	*	Returns whether the handler has a valid session running
	*
	*	@return boolean
	*/
	public function getStatus()
	{
		return $this->Verified_Code;
	}
	
	/*
	*	Returns current Bearer Token
	*
	*	@return string
	*/
	public function getToken()
	{
		return $this->Access_Token;
	}
	
	/*
	*	Returns current Refresh Token
	*
	*	@return string
	*/
	public function getRefreshToken()
	{
		return $this->RefreshToken;
	}
	
	/*
	*	Returns timestamp of when the current API session will expire
	*
	*	@return string
	*/
	public function getExpiration()
	{
		return $this->RefreshTime;
	}
	
	/*
	*	Adds a node onto the current route and returns $this for fluent interface
	*
	*	@param string $key
	*	@param string|NULL $value
	*	
	*	@return \Crester\Core\CREST
	*/
	public function node($key, $value = NULL)
	{
		if(!isset($this->APIRoute))
		{
			$this->APIRoute = new \SPLQueue();
		}
		$this->APIRoute->enqueue(new RouteNode($key, $value));
		return $this;
	}
	
	/*
	*	Gets API response from current route using GET method
	*
	*	@return array
	*/
	public function get($authorize = null)
	{
		$response = $this->makeCall('GET', $this->determineAuth($authorize));
		$this->APIRoute = new \SPLQueue();
		return json_decode($response, true);
	}
	
	/*
	*	Makes a POST call to the current route with the given data
	*
	*	@param array $data
	*
	*	@return array
	*/
	public function post(array $data = [], $authorize = null)
	{
		$response = $this->makeCall('POST', $this->determineAuth($authorize), $data);
		$this->APIRoute = new \SPLQueue();
		return json_decode($response, true);
	}
	
	/*
	*	Makes a PUT call to the current route with the given data
	*
	*	@param array $data
	*
	*	@return array
	*/
	public function put(array $data = [], $authorize = null)
	{
		$response = $this->makeCall('PUT', $this->determineAuth($authorize), $data);
		$this->APIRoute = new \SPLQueue();
		return json_decode($response, true);
	}
	
	/*
	*	Makes a DELETE call to the current route
	*
	*	@return array
	*/
	public function delete($authorize = null)
	{
		$response = $this->makeCall('DELETE', $this->determineAuth($authorize));
		$this->APIRoute = new \SPLQueue();
		return json_decode($response, true);
	}

	/*
	*	Determines which Authorization scheme to use according to settings and call parameters
	*
	*	@param null|boolean $authorize
	*
	*	@return integer
	*/
	protected function determineAuth($authorize)
	{
		// if global is true, only reason to switch is if $authorize is false
		if($this->authorize === true && $authorize === false)
			return self::AUTHORIZATION_NONE;
		
		// if global is false and $authorize is not true, switch
		if($this->authorize === false && $authorize !== true)
			return self::AUTHORIZATION_NONE;

		return self::AUTHORIZATION_BEARER;;
	}
	
    /*
	*	Verifies Authorization code and sets Access Token
	*
	*	@throws \Crester\Exceptions\CRESTAPIException
	*	
	*	@return void
	*/
	public function verifyCode()
	{
		// if api call doesn't return an error, parse it and set values
		if($Result = $this->callAPI(self::CREST_LOGIN, "POST", self::AUTHORIZATION_BASIC, array("grant_type" => 'authorization_code', 'code' => $this->Authorization_Code)))
		{
			$Result = \json_decode($Result, true);
			if(isset($Result['access_token']) && $Result['access_token'] != "")
			{
				$this->Access_Token = $Result['access_token'];
				$this->Verified_Code = true;
				$this->RefreshToken = $Result['refresh_token'];
				//Access Tokens are valid for 20mins and must be refreshed after that
				$this->RefreshTime = \time()+(60*20);
			}
			else
			{
				throw new CRESTAPIException('Error: invalid API response. '. implode(', ', $Result));
			}
		}
		// else, return false
		else
		{
			throw new CRESTAPIException('Error: cURL returned an error');
		}
	}

	/*
	*	Specialized call to get character info of logged in user
	*
	*	@return array|false
	*/
	public function getCharacterInfo()
	{
		// if api call doesn't return an error, parse it and set values
		if($Result = $this->callAPI(self::CREST_VERIFY, "GET", self::AUTHORIZATION_BEARER, array()))
		{
			return \json_decode($Result, true);
		}
		// else, return false
		else
		{
			return false;
		}
	}
	
	/*
	*	Make a custom call to given URL with given Method (GET, POST, PUT, DELETE)
	*
	*	@param string $URL
	*	@param string $Method
	*
	*	@return array|false
	*/
	public function customCall($URL, $Method, $authorize = null)
	{
		// if api call doesn't return an error, parse it and set values
		if($Result = $this->callAPI($URL, $Method, $this->determineAuth($authorize), array()))
		{
			return \json_decode($Result, true);
		}
		// else, return false
		else
		{
			return false;
		}
	}
	
	/*
	*	Uses Refresh Token to get fresh Access Token
	*
	*	@return boolean
	*/
	protected function checkRefresh()
	{
		// if connection doesn't require authorization, skip checking refresh
		if(!$this->authorize)
			return true;
		// check if access token is valid
		if(\time() >= $this->RefreshTime)
		{
			return $this->refresh();
		}
		// if access token is valid, return true
		else
		{
			return true;
		}
	}

	protected function refresh()
	{
		// if call did not throw an error, parse result and set new AccessToken
		if($Result = $this->callAPI(self::CREST_LOGIN, "POST", self::AUTHORIZATION_BASIC, array("grant_type" => 'refresh_token', 'refresh_token' => $this->RefreshToken)))
		{
			$Result = \json_decode($Result, true);
			$this->Access_Token = $Result['access_token'];
			$this->RefreshToken = $Result['refresh_token'];
			//Access Tokens are valid for 20mins and must be refreshed after that
			$this->RefreshTime = time()+(60*20);
			return true;
		}
		else
		{
			return false;
		}
	}

	/*
	*	Takes in path (SplQueue) and start recursive calls
	*
	*	@param string $Method
	*	@param array $Data
	*
	*	@return string|false
	*/
	protected function makeCall($Method, $Authorize, array $Data = [])
	{
		// Check that the route is a valid queue
		if($this->APIRoute->count() > 0)
		{
			$this->UsedRoute = self::CREST_AUTH_ROOT;
			try{
				$LeafURL = $this->recursiveCall(self::CREST_AUTH_ROOT);
			}
			catch(CRESTAPIException $e){
				echo $e;
				exit;
			}
			// check cache
			if($Result = $this->Cache->crestCheck($LeafURL, $this->APIRoute->bottom()->Key.' '.$this->APIRoute->bottom()->Value))
			{
				return $Result;
			}
			// if nothing in cache, call API
			else
			{
				if($Result = $this->callAPI($LeafURL, $Method, self::AUTHORIZATION_BEARER, $Data))
				{
					$this->Cache->crestUpdate($this->UsedRoute, $this->APIRoute->bottom()->Key.' '.$this->APIRoute->bottom()->Value, $Result);
					return $Result;
				}
				else
				{
					return false;
				}
			}
		}
		else
		{
			return false;
		}
	}

	/*
	*	Runs recursive calls down the route
	*
	*	@param string $URL
	*
	*	@return string|NULL
	*/
	protected function recursiveCall($URL)
	{
		// check cache
		if($Result = $this->Cache->crestCheck($this->UsedRoute, $this->APIRoute->bottom()->Key.' '.$this->APIRoute->bottom()->Value))
		{
			try{
				// search result for next part of path
				$NewURL = $this->search(\json_decode($Result, true), $this->APIRoute->bottom());
			}
			catch(CRESTAPIException $e){
				echo $e;
				exit;
			}
		}
		// if nothing in cache, call API
		else
		{
			if($Result = $this->callAPI($URL, "GET", self::AUTHORIZATION_BEARER, array()))
			{
				$this->Cache->crestUpdate($this->UsedRoute, $this->APIRoute->bottom()->Key.' '.$this->APIRoute->bottom()->Value, $Result);
				try{
					$NewURL = $this->search(\json_decode($Result, true), $this->APIRoute->bottom());
				}
				catch(CRESTAPIException $e){
					echo $e;
					exit;
				}
			}
			else
			{
				return;
			}
		}
		// if search returned no results, search for pagination
		if(empty($NewURL))
		{
			try{
				$NextPage = $this->search(\json_decode($Result, true), new RouteNode("next"));
			}
			catch(CRESTAPIException $e){
				echo $e;
				exit;
			}
			// if no pagination found, throw exception
			if(empty($NextPage))
			{
				throw new CRESTAPIException($this->APIRoute->bottom()->Key." and pagination not found in API response", 100);
			}
			// else, get next page
			else
			{
				try{
					return $this->recursiveCall($NextPage[0]['href']);
				}
				catch(CRESTAPIException $e){
					echo $e;
					exit;
				}
				catch(\Exception $e){
					echo $e;
					exit;
				}
			}
		}
		else
		{
			// if the leaf of the route is the only part left, return it's URL
			if($this->APIRoute->count() <= 1)
			{
				return $NewURL[0]['href'];
			}
			// else, keep calling recursively
			else
			{
				// remove last part of route
				$this->UsedRoute .= "|".$this->APIRoute->dequeue()->Key."|";
				try{
					return $this->recursiveCall($NewURL[0]['href']);
				}
				catch(CRESTAPIException $e){
					echo $e;
					exit;
				}
			}
		}
	}

	/*
	*	Makes a call to the CREST API
	*
	*	@param string $URL
	*	@param string $Method
	*	@param integer $AuthorizationType
	*	@param array $Data
	*
	*	@return string|false
	*/
	protected function callAPI($URL, $Method, $AuthorizationType, array $Data = [])
	{
		// set common settings
		$Options = array(
			CURLOPT_URL => $URL,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "base64",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
		);
		// differentiate between authentication methods
		switch($AuthorizationType)
		{
			case self::AUTHORIZATION_BASIC:
				$encode=base64_encode($this->client_id.":".$this->secret_key);
				$Host = explode("/", $URL);
				$Options[CURLOPT_HTTPHEADER] = array(
					"content-type: application/json",
					"host: ".$Host[2],
					"authorization: Basic ".$encode
				);
				break;
			case self::AUTHORIZATION_BEARER:
				// check that access token is valid and refresh if needed
				if($this->refresh())
				{
					$Host = \explode("/", $URL);
					$Options[CURLOPT_HTTPHEADER] = array(
						"content-type: application/json",
						"host: ".$Host[2],
						"authorization: Bearer ".$this->Access_Token
					);
				}
		}
		// differentiate between HTTP methods
		if($Method == "POST")
		{
			$Options[CURLOPT_POST] = true;
			$Options[CURLOPT_POSTFIELDS] = json_encode($Data);
		}
		elseif($Method == "GET")
		{
			$Options[CURLOPT_HTTPGET] = true;
		}
		elseif($Method == "PUT")
		{
			$Options[CURLOPT_PUT] = true;
			$Options[CURLOPT_POSTFIELDS] = json_encode($Data);
		}
		elseif($Method == "DELETE")
		{
			$Options[CURLOPT_CUSTOMREQUEST]="DELETE";
		}
		// apply options and send request
		$this->RateLimiter->limit();
		$curl = \curl_init();
		\curl_setopt_array($curl, $Options);
		$response = \curl_exec($curl);
		$err = \curl_error($curl);
		\curl_close($curl);
		if($err)
		{
			echo "cURL Error #:" . $err;
			return false;
		}
		else
		{
			return $response;
		}
	}

	/*
	*	Determines which search function to use and starts search
	*
	*	@param array $array
	*	@param \Crester\Core\RouteNode $routenode
	*	
	*	@return array
	*/
	protected function search(array $array, $routenode)
	{
		switch($routenode->Type)
		{
			// Search for key
			case $routenode::TYPE_KEY_SEARCH:
				return $this->ksearch($array, $routenode->Key);
			// Search for key-value pair
			case $routenode::TYPE_KEY_VALUE:
				return $this->kvsearch($array, $routenode->Key, $routenode->Value);
			// Search for key path
			case $routenode::TYPE_KEY_PATH:
				// search down the path and return last result
				for($i = 0; $i < \count($routenode->Key); $i++)
				{
					$result = $this->ksearch($array, $routenode->Key[$i]);
				}
				return $result;
			default:
				throw new CRESTAPIException("Unknown RouteNode type: ".$routenode->Type, 101);
		}
	}
	
	/*
	*	Starts Key-Value recursive search on multi-arrays
	*
	*	@param array $array
	*	@param string $key
	*	@param string $value
	*
	*	@return array
	*/
	protected function kvsearch(array $array, $key, $value)
	{
		$results = array();
		$this->kvsearch_r($array, $key, $value, $results);
		return $results;
	}

	/*
	*	Does recursive search on multi-arrays to find key-value pairs
	*
	*	@param array $array
	*	@param string $key
	*	@param string $value
	*	@param &array $results
	*
	*	@return void
	*/
	protected function kvsearch_r(array $array, $key, $value, &$results)
	{
		if (isset($array[$key]) && $array[$key] == $value) {
			$results[] = $array;
		}
	
		foreach ($array as $subarray) {
			$this->kvsearch_r($subarray, $key, $value, $results);
		}
	}
	
	/*
	*	starts recursive search on multi-arrays to find key
	*
	*	@param array $array
	*	@param string $key
	*
	*	@param array
	*/
	protected function ksearch(array $array, $key){
		// add new search option for key-value
		$results = array();
		$this->ksearch_r($array, $key, $results);
		return $results;
	}

	/*
	*	Does recursive search on multi-arrays to find key
	*
	*	@param array $array
	*	@param string $key
	*	@param &array $results
	*/
	protected function ksearch_r(array $array, $key, &$results){
		if (isset($array[$key])) {
			$results[] = $array[$key];
		}
	
		foreach ($array as $subarray) {
			$this->ksearch_r($subarray, $key, $results);
		}
	}
}