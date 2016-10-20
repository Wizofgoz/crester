<?php
namespace Crester\Cache;

interface CacheInterface
{
	public function __construct(array $Config);
	public function crestCheck($Route, $Args);
	public function crestUpdate($Route, $Args, $Response);
	public function xmlCheck($AccessCode, $AccessType, $Scope, $EndPoint, $Args);
	public function xmlUpdate($Data, $CachedUntil, $AccessCode, $AccessType, $Scope, $EndPoint, $Args);
}