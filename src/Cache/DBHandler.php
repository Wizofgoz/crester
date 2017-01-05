<?php
namespace Crester\Cache;
use \Crester\Cache\CacheInterface;
class DBHandler implements CacheInterface
{
	public function __construct(array $Config)
	{
		try {
			$conn = new PDO($Config['DB_Type']":host=".$Config['Host'].";dbname=".$Config['Name'], $Config['User'], $Config['Password']);
			// set the PDO error mode to exception
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return $conn;
		}catch(\PDOException $e){
			echo $e;
			return false;
		}
	}
	
	public function crestCheck($Route, $Args)
	{
		//if there's a fresh entry in the cache, return it
		$stmt = $this->Driver->prepare("SELECT * FROM CREST_Cache WHERE Route = :route AND Args = :args AND CacheUntil > NOW()");
		if($stmt->execute(array(":route"=>$Route, ":args"=>$Args)) > 0)
		{
			$Cached = $stmt->fetchAll();
			return $Cached[0]['Response'];
		}
		//else, return false
		else
		{
			return false;
		}
	}
	
	public function crestUpdate($Route, $Args, $Response)
	{
		//if there's an entry in the cache, update it
		$stmt = $this->Driver->prepare("SELECT id FROM CREST_Cache WHERE Route = :route AND Args = :args");
		if($stmt->execute(array(":route"=>$Route, ":args"=>$Args)) > 0)
		{
			$Cached = $stmt->fetchAll();
			$CCID = $Cached[0]['id'];
			$stmt = $this->Driver->prepare("UPDATE CREST_Cache SET Response = :response, CacheUntil = DATE_ADD(NOW(), INTERVAL 1 DAY) WHERE CCID = :ccid");
			if($stmt->execute(array(":response"=>$Response, ":ccid"=>$CCID)) > 0)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		//else, insert new
		else
		{
			$stmt = $this->Driver->prepare("INSERT INTO CREST_Cache (Route, Args, Response, CacheUntil) VALUES (:route, :args, :response, DATE_ADD(NOW(), INTERVAL 1 DAY))");
			if($stmt->execute(array(":route"=>$Route, ":args"=>$Args, ":response"=>$Response)) > 0)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}
	
	public function xmlCheck($AccessCode, $AccessType, $Scope, $EndPoint, $Args)
	{
		$ArgStr = "";
		if(!empty($Args))
		{
			foreach($Args as $Arg)
			{
				$ArgStr .= "&".$Arg['Name']."=".$Arg['Value'];
			}
		}
		$stmt = $this->Driver->prepare("SELECT Data FROM Cache WHERE KeyID = :AccessCode AND VCode = :AccessType AND Scope = :Scope AND EndPoint = :EndPoint AND Args = :Args AND CachedUntil > NOW()");
		$stmt->execute(array(":AccessCode"=>$AccessCode, ":AccessType"=>$AccessType, ":Scope"=>$Scope, ":EndPoint"=>$EndPoint, ":Args"=>$ArgStr));
		// if there's an entry in the cache, return it
		if($stmt->rowCount() > 0)
		{
			$result = $stmt->fetchAll();
			return $result['Data'];
		}
		// else, return false
		else
		{
			return true;
		}
	}
	
	public function xmlUpdate($Data, $CachedUntil, $AccessCode, $AccessType, $Scope, $EndPoint, $Args)
	{
		$ArgStr = "";
		if(!empty($Args))
		{
			foreach($Args as $Arg)
			{
				$ArgStr .= "&".$Arg['Name']."=".$Arg['Value'];
			}
		}
		$stmt = $this->Driver->prepare("SELECT Data FROM Cache WHERE KeyID = :AccessCode AND VCode = :AccessType AND Scope = :Scope AND EndPoint = :EndPoint AND Args = :Args AND CachedUntil > NOW()");
		$stmt->execute(array(":AccessCode"=>$AccessCode, ":AccessType"=>$AccessType, ":Scope"=>$Scope, ":EndPoint"=>$EndPoint, ":Args"=>$ArgStr));
		// check if there's an old entry in the cache
		if($stmt->rowCount() > 0)
		{
			// update
			$stmt = $this->Driver->prepare("UPDATE Cache SET Data = :Data, CachedUntil = :CachedUntil WHERE KeyID = :AccessCode AND VCode = :AccessType AND Scope = :Scope AND EndPoint = :EndPoint AND Args = :Args");
			if($stmt->execute(array(":Data"=>$Data, ":AccessCode"=>$AccessCode, ":AccessType"=>$AccessType, ":Scope"=>$Scope, ":EndPoint"=>$EndPoint, ":Args"=>$ArgStr, ":CachedUntil"=>$CachedUntil)))
			{
				return true;
			}
			else
			{
				throw new XMLAPIException("Error updating cache", 101);
			}
		}
		else
		{
			// insert
			$stmt = $this->Driver->prepare("INSERT INTO Cache (Data, CachedUntil, KeyID, VCode, Scope, EndPoint, Args) VALUES (:Data, :CachedUntil, :AccessCode, :AccessType, :Scope, :EndPoint, :Args)");
			if($stmt->execute(array(":Data"=>$Data, ":AccessCode"=>$AccessCode, ":AccessType"=>$AccessType, ":Scope"=>$Scope, ":EndPoint"=>$EndPoint, ":Args"=>$ArgStr, ":CachedUntil"=>$CachedUntil)))
			{
				return true;
			}
			else
			{
				throw new XMLAPIException("Error inserting to cache", 102);
			}
		}
	}
}
?>
