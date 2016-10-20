<?php
namespace Crester\Core;

abstract class CRESTBase
{
	/*
	*	URL to root of CREST API (Authenticated)
	*/
	const CREST_AUTH_ROOT = 'https://crest-tq.eveonline.com/';
	/*
	*	URL to root of CREST API (Unauthenticated - depreciated)
	*/
	const CREST_PUBLIC_ROOT = 'https://public-crest.eveonline.com/';
	/*
	*	URL for API authentication
	*/
	const CREST_LOGIN = 'https://login.eveonline.com/oauth/token';
	/*
	*	URL for verifying login and getting character information
	*/
	const CREST_VERIFY = 'https://login.eveonline.com/oauth/verify';
	/*
	*	Constant for designating a call follows the Bearer Token authentication schema
	*/
	const AUTHORIZATION_BEARER = 0;
	/*
	*	Constant for designating a call follows the Basic authentication schema
	*/
	const AUTHORIZATION_BASIC = 1;
}