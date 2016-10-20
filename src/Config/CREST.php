<?php
return [
	/*
  	*   Set values for interacting with the CREST API
  	*/
	'client_id'		=>	'your_client_id_here',
	'secret_key'	=>	'your_secret_key_here',
	'user_agent'	=>	'CREST-Handler',
	'limiter'		=>	[
			'limit'		=>	60,
			'frequency'	=>	60,
	]
]