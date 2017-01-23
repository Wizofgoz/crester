[![Latest Stable Version](https://poser.pugx.org/wizofgoz/crester/v/stable)](https://packagist.org/packages/wizofgoz/crester)
[![Latest Unstable Version](https://poser.pugx.org/wizofgoz/crester/v/unstable)](https://packagist.org/packages/wizofgoz/crester)
[![Total Downloads](https://poser.pugx.org/wizofgoz/crester/downloads)](https://packagist.org/packages/wizofgoz/crester)
[![License](https://poser.pugx.org/wizofgoz/crester/license)](https://packagist.org/packages/wizofgoz/crester)
# CRESTer 
Eve Online's CREST API Library using fluent, english-like syntax

## USAGE:
1.  Set configuration in src/Config files<br>
    a.  CREST.php - for setting configuration concerning how the library talks with the API<br>
    b.  Cache.php - for setting configuration concerning a cache that the library utilizes
2.  Initialize a Crester object
3.  Redirect to Eve Online SSO
4.  Handle Callback
5.  Make calls on returned object

## Functions
### Crester Class
redirect() - redirects visitor to Eve Online SSO for authentication<br>
handleCallback($AuthCode, $State = '') - creates a Crest object and handles final authentication with API<br>
fromRefreshToken($Token) - returns a Crest object authenticated by the given Refresh Token<br>
crest() - returns the current connection to the CREST API<br>
xml() - returns the current connection to the XML API<br>

### Crest Class
setAuthCode($AuthCode) - updates the connection's used Authorization Code and verifies it<br>
getStatus() - returns true/false whether the connection is ready to make calls to the API<br>
getToken() - returns the current token being used to make requests<br>
getRefreshToken() - returns the current refresh token<br>
getExpiration() - returns when the current token will expire<br>
node($key, $value = NULL) - adds a node to the route to traverse down the API tree<br>
get() - makes a GET call with the current route and returns the result as an array<br>
post($data = []) - makes a POST call with the current route and given data key/values and returns the result as an array<br>
put($data = []) - makes a PUT call with the current route and given data key/values and returns the result as an array<br>
delete() - makes a DELETE call with the current route and returns the result as an array<br>
verifyCode() - makes specialized call to verify Authorization Code (called automatically)<br>
getCharacterInfo() - makes specialized call to retrieve information about the logged-in character<br>
customCall($URL, $Method) - makes a call to the specified URL with the given method (GET, POST, PUT, DELETE)<br>

### XML Class
setToken($Token) - sets the access token to use when interacting with API (called automatically)<br>
setKey($Key) - sets call to use API Key Authorization. $Key is an associative array with indexes "KeyID" and "VCode"<br>
scope($Scope) - sets the scope of the call<br>
endPoint($EndPoint) - sets the endpoint the call will use<br>
accessType($AccessType) - sets the type of access the call will request. Only for CREST Authorization<br>
get($Args = [], $Key = NULL) - makes the built call against the API. $Key is an alternate to using setKey() and uses the same syntax<br>
clear() - clears the currently built call (called automatically by get())<br>

## Adding Nodes
Nodes that are added to specify the route that a call will use to reach it's destination can follow one of three formats:

### Simple Key Search
In Example 1 below, the first node added to the route uses a simple key search. In this case, after calling the base URL, CRESTer will search the returned json for a key called "constellations" and calls the URL in that item's "href".

### Key-Value Search
Also demonstrated in Example 1 is adding a key-value search node, shown by the second call to node(). In the example, the node is told to search for a key called "name" that has a value of "Joas".

## Example 1: Getting Information for a Constellation

*Initialize the CRESTer object*<br><br>
`$crester = new \Crester\Crester();`<br><br>
*Make redirect to SSO*<br><br>
`$crester->redirect();`<br><br>
*Handle callback from SSO*<br><br>
`$crest = $crester->handleCallback($_GET['code'], isset($_GET['state']) ? $_GET['state'] : '');`<br><br>
*Define call using fluent interface syntax*<br><br>
`$Joas = $crest->node('constellations')->node('name', 'Joas')->get();`<br><br>
*View the results*
`var_dump($Joas);`<br><br>
```php
array(4) { 
	["position"]=> array(3) { 
		["y"]=> float(3.3836265012848E+16) 
		["x"]=> float(-4.9173916281706E+16) 
		["z"]=> float(-4.2057063709409E+16) 
	} 
	["region"]=> array(1) { 
		["href"]=> string(48) "https://crest-tq.eveonline.com/regions/10000001/" 
	} 
	["systems"]=> array(7) { 
		[0]=> array(3) { 
			["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000112/" 
			["id"]=> int(30000112) 
			["id_str"]=> string(8) "30000112" 
		} 
		[1]=> array(3) { 
			["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000113/" 
			["id"]=> int(30000113) 
			["id_str"]=> string(8) "30000113" 
		} 
		[2]=> array(3) { 
			["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000114/" 
			["id"]=> int(30000114) 
			["id_str"]=> string(8) "30000114" 
		} 
		[3]=> array(3) { 
			["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000115/" 
			["id"]=> int(30000115) 
			["id_str"]=> string(8) "30000115" 
		} 
		[4]=> array(3) { 
			["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000116/" 
			["id"]=> int(30000116) 
			["id_str"]=> string(8) "30000116" 
		} 
		[5]=> array(3) { 
			["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000117/" 
			["id"]=> int(30000117) 
			["id_str"]=> string(8) "30000117" 
		} 
		[6]=> array(3) { 
			["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000118/" 
			["id"]=> int(30000118) 
			["id_str"]=> string(8) "30000118" 
		} 
	} 
	["name"]=> string(4) "Joas" 
}
```

