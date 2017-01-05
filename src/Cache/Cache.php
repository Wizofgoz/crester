<?php
namespace Crester\Cache;
class Cache
{
	/*
	*	Instance of driver for cache
	*
	*	@var \Crester\Cache\CacheInterface
	*/
	private $Driver;
	
	/*
	*	Whether the cache is enabled
	*
	*	@var boolean
	*/
	private $Enabled;
	
	/*
	*	Default length to cache data (in seconds)
	*
	*	@var integer
	*/
	private $Default_Cache_Length;
	
	/*
	*	Initialize Object
	*
	*	@param boolean $Enabled
	*	@param \Crester\Cache\CacheInterface $Driver
	*	@param integer $Default
	*
	*	@return void
	*/
	public function __construct($Enabled = false, CacheInterface $Driver = NULL, $Default = 60*20)
	{
		$this->Driver = $Driver;
		$this->Enabled = $Enabled;
		$this->Default_Cache_Length = $Default;
	}
	
	/*
	*	Checks CREST cache - if there is a fresh entry, return it, else return false
	*
	*	@param string $Route
	*	@param string $Args
	*
	*	@return boolean|array
	*/
	public function crestCheck($Route, $Args){
		if($this->Enabled){
			return $this->Driver->crestCheck($Route, $Args);
		}
		return false;
	}
	
	/*
	*	Updates CREST cache
	*
	*	@param string $Route
	*	@param string $Args
	*	@param string $Response
	*
	*	@return boolean
	*/
	public function crestUpdate($Route, $Args, $Response){
		if($this->Enabled){
			return $this->Driver->crestUpdate($Route, $Args, $Response);
		}
		return false;
	}
	
	/*
	*	Returns XML data if there is a valid entry in the DB, otherwise false
	*
	*	@param string $AccessCode
	*	@param string $AccessType
	*	@param string $Scope
	*	@param string $EndPoint
	*	@param string $Args
	*
	*	@return boolean|array
	*/
	public function xmlCheck($AccessCode, $AccessType, $Scope, $EndPoint, $Args){
		if($this->Enabled){
			return $this->Driver->xmlCheck($AccessCode, $AccessType, $Scope, $EndPoint, $Args);
		}
		return false;
	}
	
	/*
	*	Updates the cache with given XML data
	*
	*	@param string $Data
	*	@param string $CachedUntil
	*	@param string $AccessCode
	*	@param string $AccessType
	*	@param string $Scope
	*	@param string $EndPoint
	*	@param string $Args
	*
	*	@return boolean
	*/
	public function xmlUpdate($Data, $CachedUntil, $AccessCode, $AccessType, $Scope, $EndPoint, $Args){
		if($this->Enabled){
			return $this->Driver->xmlUpdate($Data, $CachedUntil, $AccessCode, $AccessType, $Scope, $EndPoint, $Args);
		}
		return false;
	}
}