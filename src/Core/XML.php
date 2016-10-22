<?php
namespace \Crester\Core;
use \Crester\Exceptions;
class XMLHandler extends XMLBase
{
	
	protected $Access_Token;
	
	protected $User_Agent = '';
	
	protected $Cache;
	
	protected $Scope;
	
	protected $EndPoint;
		
	protected $Args = [];
	
	protected $AccessType = 'character'; //	defaults to character
	
	public function __construct($token, $user_agent, CacheInterface $Cache)
	{
		$this->Access_Token = $token;
		$this->User_Agent = $user_agent;
		$this->Cache = $Cache;
	}
	
	public function setToken($Token)
	{
		$this->Access_Token = $Token;
	}
	
	public function scope($Scope)
	{
		if(isset(self::ALLOWED_SCOPES[$Scope]))
		{
			$this->Scope = self::ALLOWED_SCOPES[$Scope];
			return $this;
		}
		throw new XMLAPIException('Invalid Scope given');
	}
	
	public function endPoint($EndPoint)
	{
		if(isset(self::ALLOWED_ENDPOINTS[$this->Scope][$EndPoint]))
		{
			$this->EndPoint = $EndPoint;
			return $this;
		}
		throw new XMLAPIException('Invalid End Point given');
	}
	
	public function accessType($AccessType)
	{
		if(isset(self::ALLOWED_ACCESS_TYPES[$AccessType]))
		{
			$this->AccessType = $AccessType;
			return $this;
		}
		throw new XMLAPIException('Invalid Access Type given');
	}
	
	public function get(array $Args = [])
	{
		$this->Args = $Args;
		return $this->Check();
	}
	
	public function clear()
	{
		$this->Scope = NULL;
		$this->EndPoint = NULL;
		$this->Args = [];
		$this->AccessType = 'character';
		return $this;
	}
	
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

	protected function setArrayValue(&$array, $stack, $value) 
	{
		if ($stack) 
		{
			$key = array_shift($stack);
			$this->setArrayValue($array[$key], $stack, $value);
			return $array;
		} 
		else 
		{
			$array = $value;
		}
	}
	
	/*
	*	Returns XML object on success, false otherwise
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
	*	Returns XML object on success, false otherwise
	*/
	protected function CallAPI()
	{
		$ArgStr = "";
		if(!empty($this->Args))
		{
			foreach($this->Args as $name => $value)
			{
				$ArgStr .= "&".$name."=".$value;
			}
		}
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => self::API_BASE.$this->Scope."/".$this->EndPoint.self::URL_EXTENSION."?accessToken=".$this->AccessCode."&accessType=$this->AccessType".$this->ArgStr,
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
