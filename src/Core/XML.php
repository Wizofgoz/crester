<?php
namespace \Crester\Core;
use \Crester\Exceptions;
class XMLHandler extends XMLBase
{
	
	private $Access_Token;
	
	public $Cache;
	
	public function __construct($token, CacheInterface $Cache)
	{
		$this->Access_Token = $token;
		$this->Cache = $Cache;
	}
	
	public function setToken($Token)
	{
		$this->Access_Token = $Token;
	}
	
	/*
	*	Returns XML object on success, false otherwise
	*/
	public function Check($Scope, $EndPoint, $Args, $AccessType = 'character'){
		if(in_array($AccessType, array('character', 'corporation')))
		{
			$result = $this->Cache->xmlCheck($this->Access_Token, $AccessType, $Scope, $EndPoint, $Args);
			if(!$result === false)
			{
				return $result;
			}
			else
			{
				return $this->CallAPI($this->Access_Token, $AccessType, $Scope, $EndPoint, $Args);
			}
		}
		else
		{
			throw new XMLAPIException("Invalid AccessType given", 111);
		}
	}
	/*
	*	Returns XML object on success, false otherwise
	*/
	private function CallAPI($AccessCode, $AccessType, $Scope, $EndPoint, $Args){
		$ArgStr = "";
		if(!empty($Args))
		{
			foreach($Args as $Arg)
			{
				$ArgStr .= "&".$Arg['Name']."=".$Arg['Value'];
			}
		}
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => self::API_BASE.$Scope."/".$EndPoint.self::URL_EXTENSION."?accessToken=".$AccessCode."&accessType=$AccessType".$ArgStr,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_HEADER, 0,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_USERAGENT => "",
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
			throw new XMLAPIException("Curl error $err", 1);
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
