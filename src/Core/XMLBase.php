<?php
namespace Crester\Core;
abstract class XMLBase
{
	/*
	*	Base of the API URL
	*
	*	@var string
	*/
	const API_BASE = 'https://api.eveonline.com/';
	
	/*
	*	File extension to append to endpoint
	*
	*	@var string
	*/
	const URL_EXTENSION = '.xml.aspx';

	/*
	*	Constant to designate an API call as using CREST for authorization
	*
	*	@var integer
	*/
	const AUTH_CREST = 1;

	/*
	*	Constant to designate an API call as using an API Key for authorization
	*
	*	@var string
	*/
	const AUTH_TOKEN = 0;
	
	/*
	*	Array of allowed scopes
	*
	*	@var array
	*/
	const ALLOWED_SCOPES = [
		'account' => 'account',
		'api' => 'api',
		'character' => 'char',
		'corporation' => 'corp',
		'eve' => 'eve',
		'map' => 'map',
		'server' => 'server',
	];
	
	/*
	*	Array of allowed endpoints
	*
	*	@var array
	*/
	const ALLOWED_ENDPOINTS = [
		//	account endpoints
		'account' => [
			'AccountStatus' => true,
			'APIKeyInfo' => true,
			'Characters' => true,
		],
		//	api endpoints
		'api' => [
			'CallList' => true,
		],
		//	character endpoints
		'char' => [
			'AccountBalance' => true,
			'AssetList' => true,
			'Blueprints' => true,
			'Bookmarks' => true,
			'CalendarEventAttendees' => true,
			'CharacterSheet' => true,
			'ChatChannels' => true,
			'Clones' => true,
			'ContactList' => true,
			'ContactNotifications' => true,
			'ContractBids' => true,
			'ContractItems' => true,
			'Contracts' => true,
			'FacWarStats' => true,
			'IndustryJobs' => true,
			'IndustryJobsHistory' => true,
			'KillLog' => true,
			'KillMails' => true,
			'Locations' => true,
			'MailBodies' => true,
			'MailingLists' => true,
			'MailMessages' => true,
			'MarketOrders' => true,
			'Medals' => true,
			'Notifications' => true,
			'NotificationTexts' => true,
			'PlanetaryColonies' => true,
			'PlanetaryLinks' => true,
			'PlanetaryPins' => true,
			'PlanetaryRoutes' => true,
			'Research' => true,
			'SkillInTraining' => true,
			'SkillQueue' => true,
			'Skills' => true,
			'Standings' => true,
			'UpcomingCalendarEvents' => true,
			'WalletJournal' => true,
			'WalletTransactions' => true,
		],
		//	corporation endpoints
		'corp' => [
			'AccountBalance' => true,
			'AssetList' => true,
			'Blueprints' => true,
			'Bookmarks' => true,
			'ContactList' => true,
			'ContainerLog' => true,
			'ContractBids' => true,
			'ContractItems' => true,
			'Contracts' => true,
			'CorporationSheet' => true,
			'CustomsOffices' => true,
			'Facilities' => true,
			'FacWarStats' => true,
			'IndustryJobs' => true,
			'IndustryJobsHistory' => true,
			'KillMails' => true,
			'Locations' => true,
			'MarketOrders' => true,
			'Medals' => true,
			'MemberMedals' => true,
			'MemberSecurity' => true,
			'MemberSecurityLog' => true,
			'MemberTracking' => true,
			'OutpostList' => true,
			'OutpostServiceDetail' => true,
			'Shareholders' => true,
			'Standings' => true,
			'StarbaseDetail' => true,
			'StarbaseList' => true,
			'Titles' => true,
			'WalletJournal' => true,
			'WalletTransactions' => true
		],
		//	eve endpoints
		'eve' => [
			'AllianceList' => true,
			'CharacterAffiliation' => true,
			'CharacterID' => true,
			'CharacterInfo' => true,
			'CharacterName' => true,
			'ConquerableStationList' => true,
			'ErrorList' => true,
			'RefTypes' => true,
			'TypeName' => true,
		],
		//	map endpoints
		'map' => [
			'FacWarSystems' => true,
			'Jumps' => true,
			'Kills' => true,
			'Sovereignty' => true,
		],
		//	server endpoints
		'server' => [
			'ServerStatus' => true,
		],
	];
	
	/*
	*	Array of allowed access types
	*
	*	@var array
	*/
	const ALLOWED_ACCESS_TYPES = [
		'character' => true,
		'corporation' => true
	];
}