<?php

return [
    /*
    *   Define what driver to use when dealing with caching of API calls
    *
    *	Supported: database, redis
    */
    'driver'=> 'database',

    'enabled'=> false,

    'default_length'=> 20 * 60,    //	20 mins

    'options'=> [
        /*
        *   Define options for the Database driver
        */
        'database'=> [
            'host'    => 'localhost',
            'database'=> '',
            'username'=> '',
            'password'=> '',
        ],

        /*'redis'=>[
            'scheme'=>'',
            'host'=>'127.0.0.1',
            'port'=>6379,
        ],*/
    ],
];
