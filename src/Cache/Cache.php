<?php
namespace \Crester\Cache;
class Cache
{
	
	private $Driver;
	
	private $Enabled;
	
	private $Default_Cache_Length;
	
	public function __construct(CacheInterface $Driver, $Enabled = true, $Default = 60*20)
	{
		$this->Driver = $Driver;
		$this->Enabled = $Enabled;
		$this->Default_Cache_Length = $Default;
	}
	
	/*
	*	Checks CREST cache - if there is a fresh entry, return it, else return false
	*/
	public function crestCheck($Route, $Args){
		if($this->Enabled)
		{
			return $this->Driver->crestCheck($Route, $Args);
		}
		else
		{
			return true;
		}
	}
	
	/*
	*	Updates CREST cache
	*/
	public function crestUpdate($Route, $Args, $Response){
		if($this->Enabled)
		{
			return $this->Driver->crestUpdate($Route, $Args, $Response);
		}
		else
		{
			return true;
		}
	}
	
	/*
	*	Returns XML data if there is a valid entry in the DB, otherwise false
	*/
	public function xmlCheck($AccessCode, $AccessType, $Scope, $EndPoint, $Args){
		if($this->Enabled)
		{
			return $this->Driver->xmlCheck($AccessCode, $AccessType, $Scope, $EndPoint, $Args);
		}
		else
		{
			return true;
		}
	}
	
	/*
	*	Updates the cache with given XML data
	*/
	public function xmlUpdate($Data, $CachedUntil, $AccessCode, $AccessType, $Scope, $EndPoint, $Args){
		if($this->Enabled)
		{
			return $this->Driver->xmlUpdate($Data, $CachedUntil, $AccessCode, $AccessType, $Scope, $EndPoint, $Args);
		}
		else
		{
			return false;
		}
	}
}