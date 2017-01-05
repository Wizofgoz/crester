<?php
namespace Crester\Core;

abstract class CRESTBase
{
	/*
	*	URL to root of CREST API (Authenticated)
	*
	*	@var string
	*/
	const CREST_AUTH_ROOT = 'https://crest-tq.eveonline.com/';

	/*
	*	URL to root of CREST API (Unauthenticated - depreciated)
	*
	*	@var string
	*/
	const CREST_PUBLIC_ROOT = 'https://public-crest.eveonline.com/';

	/*
	*	URL for API authentication
	*
	*	@var string
	*/
	const CREST_LOGIN = 'https://login.eveonline.com/oauth/token';

	/*
	*	URL for verifying login and getting character information
	*
	*	@var string
	*/
	const CREST_VERIFY = 'https://login.eveonline.com/oauth/verify';

	/*
	*	Constant for designating a call follows the Bearer Token authentication schema
	*
	*	@var integer
	*/
	const AUTHORIZATION_BEARER = 0;

	/*
	*	Constant for designating a call follows the Basic authentication schema
	*
	*	@var integer
	*/
	const AUTHORIZATION_BASIC = 1;

	/*
	*	Constant for designating a call requires no authorization
	*
	*	@var integer
	*/
	const AUTHORIZATION_NONE = 2;
}