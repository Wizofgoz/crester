# CRESTer
Eve Online's CREST API Library using fluent, english-like syntax

# USAGE:
1.  Set configuration in src/Config files<br>
    a.  CREST.php - for setting configuration concerning how the library talks with the API<br>
    b.  Cache.php - for setting configuration concerning a cache that the library utilizes
2.  Initialize a Crester object
3.  Redirect to Eve Online SSO
4.  Handle Callback
5.  Make calls on returned object

# Functions
##1.  Crester Class<br>
    a.  redirect() - redirects visitor to Eve Online SSO for authentication<br>
    b.  handleCallback($AuthCode, $State = '') - creates a Crest object and handles final authentication with API<br>
    c.  fromRefreshToken($Token) - returns a Crest object authenticated by the given Refresh Token<br>
    d.  crest() - returns the current connection to the CREST API<br>
    e.  xml() - returns the current connection to the XML API
##2.  Crest Class<br>
    a.  setAuthCode($AuthCode) - updates the connection's used Authorization Code and verifies it<br>
    b.  getStatus() - returns true/false whether the connection is ready to make calls to the API<br>
    c.  getToken() - returns the current token being used to make requests<br>
    d.  getRefreshToken() - returns the current refresh token<br>
    e.  getExpiration() - returns when the current token will expire<br>
    f.  node($key, $value = NULL) - adds a node to the route to traverse down the API tree<br>
    g.  get() - makes a GET call with the current route and returns the result as an array<br>
    h.  post($data = []) - makes a POST call with the current route and given data key/values and returns the result as an array<br>
    i.  put($data = []) - makes a PUT call with the current route and given data key/values and returns the result as an array<br>
    j.  delete() - makes a DELETE call with the current route and returns the result as an array<br>
    k.  verifyCode() - makes specialized call to verify Authorization Code (called automatically)<br>
    l.  getCharacterInfo() - makes specialized call to retrieve information about the logged-in character<br>
    m.  customCall($URL, $Method) - makes a call to the specified URL with the given method (GET, POST, PUT, DELETE)

##Example 1: Getting Information for a Constallation

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
array(4) { ["position"]=> array(3) { ["y"]=> float(3.3836265012848E+16) ["x"]=> float(-4.9173916281706E+16) ["z"]=> float(-4.2057063709409E+16) } ["region"]=> array(1) { ["href"]=> string(48) "https://crest-tq.eveonline.com/regions/10000001/" } ["systems"]=> array(7) { [0]=> array(3) { ["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000112/" ["id"]=> int(30000112) ["id_str"]=> string(8) "30000112" } [1]=> array(3) { ["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000113/" ["id"]=> int(30000113) ["id_str"]=> string(8) "30000113" } [2]=> array(3) { ["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000114/" ["id"]=> int(30000114) ["id_str"]=> string(8) "30000114" } [3]=> array(3) { ["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000115/" ["id"]=> int(30000115) ["id_str"]=> string(8) "30000115" } [4]=> array(3) { ["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000116/" ["id"]=> int(30000116) ["id_str"]=> string(8) "30000116" } [5]=> array(3) { ["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000117/" ["id"]=> int(30000117) ["id_str"]=> string(8) "30000117" } [6]=> array(3) { ["href"]=> string(53) "https://crest-tq.eveonline.com/solarsystems/30000118/" ["id"]=> int(30000118) ["id_str"]=> string(8) "30000118" } } ["name"]=> string(4) "Joas" }
```

