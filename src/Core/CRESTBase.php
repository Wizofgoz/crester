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