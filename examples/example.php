<?php
require_once(__DIR__.'/../vendor/autoload.php');

//	Initialize library
$crester = new Crester\Crester();
//	If this page has been called as a callback from the SSO, handle it and use connection
if(isset($_GET['code']))
{
	$crest = $crester->handleCallback($_GET['code'], isset($_GET['state']) ? $_GET['state'] : '');
	var_dump($crest->node('constellations')->node('name', 'Joas')->get());
}
//	Else, redirect to the SSO
else
{
	$crester->redirect();
}