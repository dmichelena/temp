<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PDO Fetch Style
    |--------------------------------------------------------------------------
    |
    | By default, database results will be returned as instances of the PHP
    | stdClass object; however, you may desire to retrieve records in an
    | array format for simplicity. Here you can tweak the fetch style.
    |
    */

    'fetch' => PDO::FETCH_OBJ,

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    //php artisan code:models //Generate Models
    'default' => env('DB_CONNECTION', 'core'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [
	    'admin' => [
		    'driver' => 'mysql',
		    'host' => env('DB_HOST_ADMIN', '127.0.0.1'),
		    'port' => env('DB_PORT_ADMIN', '3306'),
		    'database' => env('DB_DATABASE_ADMIN', 'forge'),
		    'username' => env('DB_USERNAME_ADMIN', 'forge'),
		    'password' => env('DB_PASSWORD_ADMIN', ''),
		    'charset' => 'utf8',
		    'collation' => 'utf8_unicode_ci',
		    'prefix' => '',
		    'strict' => true,
		    'engine' => null,
	    ],
	    'identity' => [
		    'driver' => 'mysql',
		    'host' => env('DB_HOST_IDENTITY', '127.0.0.1'),
		    'port' => env('DB_PORT_IDENTITY', '3306'),
		    'database' => env('DB_DATABASE_IDENTITY', 'forge'),
		    'username' => env('DB_USERNAME_IDENTITY', 'forge'),
		    'password' => env('DB_PASSWORD_IDENTITY', ''),
		    'charset' => 'utf8',
		    'collation' => 'utf8_unicode_ci',
		    'prefix' => '',
		    'strict' => true,
		    'engine' => null,
	    ],
	    'core' => [
		    'driver' => 'mysql',
		    'host' => env('DB_HOST_CORE', '127.0.0.1'),
		    'port' => env('DB_PORT_CORE', '3306'),
		    'database' => env('DB_DATABASE_CORE', 'forge'),
		    'username' => env('DB_USERNAME_CORE', 'forge'),
		    'password' => env('DB_PASSWORD_CORE', ''),
		    'charset' => 'utf8',
		    'collation' => 'utf8_unicode_ci',
		    'prefix' => '',
		    'strict' => true,
		    'engine' => null,
	    ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer set of commands than a typical key-value systems
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'cluster' => false,

        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],

    ],

];
