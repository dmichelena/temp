<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

	'firebase' => [
		'type' => env('FIREBASE_TYPE', ''),
		'project_id' => env('FIREBASE_PROJECT_ID', ''),
		'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID', ''),
		'private_key' => env('FIREBASE_PRIVATE_KEY', ''),
		'client_email' => env('FIREBASE_CLIENT_EMAIL', ''),
		'client_id' => env('FIREBASE_CLIENT_ID', ''),
		'auth_uri' => env('FIREBASE_AUTH_URI', ''),
		'token_uri' => env('FIREBASE_TOKEN_URI', ''),
		'auth_provider_x509_cert_url' => env('FIREBASE_AUTH_CERT_URL', ''),
		'client_x509_cert_url' => env('FIREBASE_CLIENT_CERT_URL', ''),
	],
];
