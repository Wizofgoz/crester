<?php
namespace Crester\Core;
/*
*	Class for simplifying the creation of each node of a route
*/
class RouteNode
{
	/*
	*	Key for API Handler to search for in response
	*	@var mixed $Key
	*/
	public $Key;
	/*
	*	Value of Key for API Handler to search for in response
	*	@var string $Value
	*/
	public $Value;
	/*
	*	Type of node. Can be TYPE_KEY_SEARCH or TYPE_KEY_VALUE
	*	@var integer $Type
	*/
	public $Type;
	/*
	*	Used when searching API response for just the given key
	*/
	const TYPE_KEY_SEARCH = 0;
	/*
	*	Used when searching API response for the given key-value pair
	*/
	const TYPE_KEY_VALUE = 1;
	/*
	*	Used when searching API response for given path of keys
	*/
	const TYPE_KEY_PATH = 2;
	/*
	*	Create instance of a Route Node
	*
	*	@param mixed $Key
	*	@param string $Value
	*/
	public function __construct($Key, $Value = NULL){
		if(!is_array($Key))
		{
			if($Value === NULL)
			{
				$this->Type = self::TYPE_KEY_SEARCH;
			}
			else
			{
				$this->Type = self::TYPE_KEY_VALUE;
			}
		}
		else
		{
			$this->Type = self::TYPE_KEY_PATH;
		}
		$this->Key = $Key;
		$this->Value = $Value;
	}
}
?>
