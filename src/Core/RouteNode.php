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
    public function __construct($Key, $Value = null)
    {
        if (!is_array($Key)) {
            if ($Value === null) {
                $this->Type = self::TYPE_KEY_SEARCH;
            } else {
                $this->Type = self::TYPE_KEY_VALUE;
            }
        } else {
            $this->Type = self::TYPE_KEY_PATH;
        }
        $this->Key = $Key;
        $this->Value = $Value;
    }
}
