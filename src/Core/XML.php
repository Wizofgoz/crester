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
use \Crester\Exceptions;
use \Crester\Cache\Cache;
class XMLHandler extends XMLBase implements \SplObserver
{
	/*
	*	User Agent Header to send to the Eve API
	*
	*	@var string
	*/
	protected $User_Agent = '';
	
	/*
	*	Cache Instance
	*
	*	@var \Crester\Cache\Cache
	*/
	protected $Cache;
	
	/*
	*	Scope of the current API call
	*
	*	@var string
	*/
	protected $Scope;
	
	/*
	*	Endpoint of the current API call
	*
	*	@var string
	*/
	protected $EndPoint;
	
	/*
	*	Arguments to pass with the current API call
	*
	*	@var string[]
	*/
	protected $Args = [];

	/*
	*	Access Token to use with a CREST Authorized API call
	*
	*	@var string
	*/
	protected $Access_Token;
	
	/*
	*	Access Type to use with a CREST Authorized API call
	*
	*	@var string
	*/
	protected $AccessType = 'character'; //	defaults to character

	/*
	*	Authorization sheme to use for the current API call
	*
	*	@var integer
	*/
	protected $AuthScheme;

	/*
	*	Key ID to use for with a API Token Authorized API call
	*
	*	@var string
	*/
	protected $KeyID;

	/*
	*	Verification Code to use with an API Token Authorized API call
	*
	*	@var string
	*/
	protected $VCode;
	
	/*
	*	Initialize the XML connection
	*
	*	@param array $config
	*	@param \Crester\Cache\Cache $Cache
	*
	*	@return void
	*/
	public function __construct(array $config, Cache $Cache)
	{
		$this->User_Agent = $config['user_agent'];
		$this->AuthScheme = $config['xmlAPI']['crestAuth'] ? self::AUTH_CREST : self::AUTH_TOKEN;
		$this->Cache = $Cache;
	}

	/*
	*	Update the Access Token when the CREST connection refreshes
	*
	*	@param \SplSubject $subject
	*
	*	@return void
	*/
	public function update(\SplSubject $subject)
	{
		$this->setToken($subject->getToken);
	}
	
	/*
	*	Set the Access Token
	*
	*	@param string $Token
	*
	*	@return void
	*/
	protected function setToken($Token)
	{
		$this->Access_Token = $Token;
	}

	/*
	*	Set the call to use API Key Authorization and set the key's values
	*
	*	@param array $Key
	*
	*	@return void
	*/
	protected function setKey(array $Key)
	{
		$this->KeyID = $Key['KeyID'];
		$this->VCode = $Key['VCode'];
		$this->AuthScheme = self::AUTH_TOKEN;
	}
	
	/*
	*	Set the scope of the current API call
	*
	*	@param string $Scope
	*
	*	@return \Crester\XML\XML
	*
	*	@throws \Crester\Exceptions\XMLAPIException
	*/
	public function scope($Scope)
	{
		if(!isset(self::ALLOWED_SCOPES[$Scope]))
		{
			throw new XMLAPIException('Invalid Scope given');
		}
		$this->Scope = self::ALLOWED_SCOPES[$Scope];
		return $this;
	}
	
	/*
	*	Set the endpoint of the current API call
	*
	*	@param string $EndPoint
	*
	*	@return \Crester\XML\XML
	*
	*	@throws \Crester\Exceptions\XMLAPIException
	*/
	public function endPoint($EndPoint)
	{
		if(!isset(self::ALLOWED_ENDPOINTS[$this->Scope][$EndPoint]))
		{
			throw new XMLAPIException('Invalid End Point given');
		}
		$this->EndPoint = $EndPoint;
		return $this;
	}
	
	/*
	*	Set the access type of the current API call
	*
	*	@param string $AccessType
	*
	*	@return \Crester\XML\XML
	*
	*	@throws \Crester\Exceptions\XMLAPIException
	*/
	public function accessType($AccessType)
	{
		if(!isset(self::ALLOWED_ACCESS_TYPES[$AccessType]))
		{
			throw new XMLAPIException('Invalid Access Type given');
		}
		$this->AccessType = $AccessType;
		return $this;
	}
	
	/*
	*	Make call to the API with current values and reset the object for a new call
	*
	*	@param string[] $Args
	*	@param array $Key
	*
	*	@return array
	*/
	public function get(array $Args = [], array $Key = null)
	{
		if($Key !== null)
			$this->setKey($Key);
		$this->Args = $Args;
		$response = $this->Check();
		$this->clear();
		return $response;
	}
	
	/*
	*	Clear the object for a new call
	*
	*	@return \Crester\XML\XML
	*/
	public function clear()
	{
		$this->Scope = NULL;
		$this->EndPoint = NULL;
		$this->Args = [];
		$this->AccessType = 'character';
		return $this;
	}
	
	/*
	*	Parse XML into array structure
	*
	*	@param string $xml
	*
	*	@return array
	*/
	protected function XMLToArray($xml)
	{
		$parser = \xml_parser_create('ISO-8859-1'); // For Latin-1 charset
		\xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0); // Dont mess with my cAsE sEtTings
		\xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1); // Dont bother with empty info
		\xml_parse_into_struct($parser, $xml, $values);
		\xml_parser_free($parser);

		$return = array(); // The returned array
		$stack = array(); // tmp array used for stacking
		foreach($values as $val)
		{
			if($val['type'] == "open")
			{
				array_push($stack, $val['tag']);
			}
			elseif($val['type'] == "close") 
			{
				array_pop($stack);
			} 
			elseif($val['type'] == "complete") 
			{
				array_push($stack, $val['tag']);
				$this->setArrayValue($return, $stack, $val['value']);
				array_pop($stack);
			}
		}
		return $return;
	}

	/*
	*	Recursively updates $array with stack's sub-items
	*
	*	@param array $array
	*	@param array $stack
	*	@param string $value
	*
	*	@return void
	*/
	protected function setArrayValue(&$array, $stack, $value) 
	{
		if ($stack) 
		{
			$key = array_shift($stack);
			$this->setArrayValue($array[$key], $stack, $value);
		} 
		else 
		{
			$array = $value;
		}
	}
	
	/*
	*	Checks cache and if miss, calls API
	*
	*	@return array
	*/
	protected function Check(){
		$result = $this->Cache->xmlCheck($this->Access_Token, $this->AccessType, $this->Scope, $this->EndPoint, $this->Args);
		//if the cache returned false, call the api
		if($result === false)
		{
			$result = $this->CallAPI($this->Access_Token, $this->AccessType, $this->Scope, $this->EndPoint, $this->Args);
		}
		return $this->XMLToArray($result);
	}

	/*
	*	Builds Authorization parameters for API call
	*
	*	@return string
	*/
	protected function GetAuthParameters()
	{
		switch($this->AuthScheme)
		{
			case self::AUTH_CREST:
				return "?accessToken=".$this->AccessCode."&accessType=$this->AccessType";
				break;
			case self::AUTH_TOKEN:
				return "?keyID=$this->KeyID&vCode=$this->VCode";
				break;
		}
	}

	/*
	*	Builds full URL to call API with
	*
	*	@return string
	*/
	protected function BuildURL()
	{
		$ArgStr = "";
		if(!empty($this->Args))
		{
			foreach($this->Args as $name => $value)
			{
				$ArgStr .= "&".$name."=".$value;
			}
		}
		return self::API_BASE.$this->Scope."/".$this->EndPoint.self::URL_EXTENSION.$this->GetAuthParameters().$this->ArgStr;
	}

	/*
	*	Calls API with current settings
	*
	*	@return string
	*
	*	@throws \Crester\Exceptions\XMLAPIException
	*/
	protected function CallAPI()
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $this->BuildURL(),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_HEADER, 0,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_USERAGENT => $this->User_Agent,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, 
			CURLOPT_HTTPHEADER => array(
				"content-type: application/xml",
				"crossdomain: true",
				"datatype: xml"
			)
		));
		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if($err)
		{
			throw new XMLAPIException("Curl error $err");
		}
		else
		{
			$Data = new \SimpleXMLElement($response);
			$data = $Data->asXML();
			$CachedUntil = date("Y-m-d H:i:s", strtotime($Data->cachedUntil));
			return ($this->Cache->xmlUpdate($data, $CachedUntil, $AccessCode, $AccessType, $Scope, $EndPoint, $Args) ? $response : false);
		}
	}
}
?>
