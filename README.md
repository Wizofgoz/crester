# Eve-Online-CREST-Handler
Classes for implementing an easy interface with Eve Online's CREST API

# USAGE:
1.  Require the autoloader
2.  Obtain a Verification Code from the EVE SSO
3.  Initialize the CRESTHandler class 
4.  Pre-defined calls are available as methods:<br>
    a.  getCharacterInfo() - gets PHP array of logged-in character's information
5.  The makeCall method takes an SplQueue or RouteNode objects and should be used as follows:<br>
    a.  Initialize the SplQueue<br>
    b.  Build out the route you wish the handler to follow down the branches of the CREST API by enqueing RouteNodes<br>
    c.  Pass the finished route into the makeCall method, specifying the final call method and any additional arguments

##Example 1: Getting Information for a Constallation

*Initialize the CRESTHandler object*<br><br>
`$Handler = new CRESTHandler($Verification_Code);`<br><br>
*Initialize the Route queue*<br><br>
`$Route = new SplQueue();`<br><br>
*Add nodes to the Route*<br><br>
`$Route->enqueue(new RouteNode('constellations')); // search for constellations in root of API`<br>
`$Route->enqueue(new RouteNode('name', 'Joas')); // search list of constellations for one with name of Joas`<br><br>
*Make recursive call with HTTP GET method*<br><br>
`$response = $Handler->makeCall($Route, "GET"); // returns decoded JSON as PHP array`<br>

