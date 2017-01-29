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
namespace Crester\Core;
use \Crester\Core\CRESTBase;
use \Crester\Cache\Cache;
use \Crester\Exceptions\CRESTAPIException;
use \Crester\Exceptions\APIResponseException;
use \Crester\Exceptions\APIAuthException;
use \Crester\Exceptions\ParseException;
use \Crester\Exceptions\ResponseSearchException;
class CREST extends CRESTBase implements \SplSubject
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
	*	List of observers attached
	*
	*	@var \SplObjectStorage
	*/
	protected $observers;

	/*
	*	HTTP Client used when communicating with API
	*
	*	@var GuzzleHttp\Client
	*/
	protected $client;
	
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
	public function __construct(array $config, $code, RateLimiter $limiter, Cache $cache, $refresh = false)
	{
		$this->client_id = $config['client_id'];
		$this->secret_key = $config['secret_key'];
		$this->user_agent = (isset($config['user_agent']) ? $config['user_agent'] : 'CRESTer');
		$this->authorize = (isset($config['authorize']) ? $config['authorize'] : false);
		$this->RateLimiter = $limiter;
		$this->Cache = $cache;
		$this->observers = new \SplObjectStorage();
		$this->client = new GuzzleHttp\Client([
			'timeout' => 30,
			'headers' => [
				'accept' => 'base64'
			]
		]);
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
	*	Attach an observer to the object
	*
	*	@param \SplObserver $observer
	*/
	public function attach(\SplObserver $observer)
    {
        $this->observers->attach($observer);
    }

    /*
    *	Detach an observer from the object
    */
    public function detach(\SplObserver $observer)
    {
        $this->observers->detach($observer);
    }

    /*
    *	Notify observers that this object has updated
    */
    public function notify()
    {
        /** @var \SplObserver $observer */
        foreach ($this->observers as $observer) {
            $observer->update($this);
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
			$this->notify();
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
		$authorize = $authorize === null ? $this->authorize : $authorize;

		// if global is true, only reason to switch is if $authorize is false
		if($this->authorize === true && $authorize === false)
			return self::AUTHORIZATION_NONE;
		
		// if global is false and $authorize is not true, switch
		if($this->authorize === false && $authorize === false)
			return self::AUTHORIZATION_NONE;

		return self::AUTHORIZATION_BEARER;
	}
	
    /*
	*	Verifies Authorization code and sets Access Token
	*
	*	@throws \Crester\Exceptions\APIAuthException
	*	
	*	@return void
	*/
	public function verifyCode()
	{
		try{
			$Result = $this->callAPI(self::CREST_LOGIN, "POST", self::AUTHORIZATION_BASIC, array("grant_type" => 'authorization_code', 'code' => $this->Authorization_Code));
			$Result = \json_decode($Result, true);
			$this->Access_Token = $Result['access_token'];
			$this->Verified_Code = true;
			$this->RefreshToken = $Result['refresh_token'];
			//Access Tokens are valid for 20mins and must be refreshed after that
			$this->RefreshTime = \time()+(60*20);
		}catch(APIResponseException $e){
			throw new APIAuthException($e->getMessage());
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

	/*
	*	Calls API to refresh the connection
	*
	*	@return boolean
	*
	*	@throws Crester\Exceptions\APIAuthException
	*/
	protected function refresh()
	{
		try{
			$Result = $this->callAPI(self::CREST_LOGIN, "POST", self::AUTHORIZATION_BASIC, array("grant_type" => 'refresh_token', 'refresh_token' => $this->RefreshToken));
			$Result = \json_decode($Result, true);
			$this->Access_Token = $Result['access_token'];
			$this->RefreshToken = $Result['refresh_token'];
			//Access Tokens are valid for 20mins and must be refreshed after that
			$this->RefreshTime = time()+(60*20);
			$this->notify();
			return true;
		}catch(APIResponseException $e){
			throw new APIAuthException($e->getMessage());
		}
	}

	/*
	*	Takes in path (SplQueue) and start recursive calls
	*
	*	@param string $Method
	*	@param integer $Authorize
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
			$LeafURL = $this->recursiveCall(self::CREST_AUTH_ROOT, $Authorize);
			// check cache
			if($Result = $this->Cache->crestCheck($LeafURL, $this->APIRoute->bottom()->Key.' '.$this->APIRoute->bottom()->Value))
			{
				return $Result;
			}
			// if nothing in cache, call API
			else
			{
				$Result = $this->callAPI($LeafURL, $Method, $Authorize, $Data);
				$this->Cache->crestUpdate($this->UsedRoute, $this->APIRoute->bottom()->Key.' '.$this->APIRoute->bottom()->Value, $Result);
				return $Result;
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
	*	@param integer $Authorize
	*
	*	@return string|NULL
	*
	*	@throws \Crester\Exceptions\ResponseSearchException
	*/
	protected function recursiveCall($URL, $Authorize)
	{
		// check cache
		if($Result = $this->Cache->crestCheck($this->UsedRoute, $this->APIRoute->bottom()->Key.' '.$this->APIRoute->bottom()->Value))
		{
			// search result for next part of path
			$NewURL = $this->search(\json_decode($Result, true), $this->APIRoute->bottom());
		}
		// if nothing in cache, call API
		else
		{
			$Result = $this->callAPI($URL, "GET", $Authorize, array());
			$this->Cache->crestUpdate($this->UsedRoute, $this->APIRoute->bottom()->Key.' '.$this->APIRoute->bottom()->Value, $Result);
			$NewURL = $this->search(\json_decode($Result, true), $this->APIRoute->bottom());
		}
		// if search returned no results, search for pagination
		if(empty($NewURL))
		{
			$NextPage = $this->search(\json_decode($Result, true), new RouteNode("next"));
			// if no pagination found, throw exception
			if(empty($NextPage))
			{
				throw new ResponseSearchException($this->APIRoute->bottom()->Key." and pagination not found in API response", 100);
			}
			// else, get next page
			else
			{
				return $this->recursiveCall($NextPage[0]['href'], $Authorize);
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
				return $this->recursiveCall($NewURL[0]['href'], $Authorize);
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
	*
	*	@throws \Crester\Exceptions\APIResponseException
	*/
	protected function callAPI($URL, $Method, $AuthorizationType, array $Data = [])
	{
		$headers = array();
		// differentiate between authentication methods
		switch($AuthorizationType)
		{
			case self::AUTHORIZATION_BASIC:
				$encode=base64_encode($this->client_id.":".$this->secret_key);
				$Host = explode("/", $URL);
				$headers = array_merge(array(
					"content-type: application/json",
					"host: ".$Host[2],
					"authorization: Basic ".$encode
				), $headers);
				break;
			case self::AUTHORIZATION_BEARER:
				// check that access token is valid and refresh if needed
				if($this->refresh())
				{
					$Host = \explode("/", $URL);
					headers = array_merge(array(
						"content-type: application/json",
						"host: ".$Host[2],
						"authorization: Bearer ".$this->Access_Token
					), $headers);
				}
		}
		$request = new Request($Method, $URL, $headers);
		// apply options and send request
		$this->RateLimiter->limit();
		if(empty($Data))
		{
			$response = $this->client->send($request);
		}
		else
		{
			$response = $this->client->send($request, ['json' => json_encode($Data)]);
		}
		$successCodes = array(200, 201, 202);
		if(!in_array($response->getStatusCode(), $successCodes))
		{
			throw new APIResponseException("cURL Error #:" . $err);
		}
		$body = (string)$response->getBody();
		$arr = json_decode($body, true);
		if(isset($arr['error']))
			throw new APIResponseException($arr['error'].'->'.$arr['error_description']);
		return $body;
	}

	/*
	*	Determines which search function to use and starts search
	*
	*	@param array $array
	*	@param \Crester\Core\RouteNode $routenode
	*	
	*	@return array
	*
	*	@throws \Crester\Exceptions\ResponseSearchException
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
				throw new ResponseSearchException("Unknown RouteNode type: ".$routenode->Type, 101);
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
			if(!is_array($subarray))
				continue;
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
			if(!is_array($subarray))
				continue;
			$this->ksearch_r($subarray, $key, $results);
		}
	}
}