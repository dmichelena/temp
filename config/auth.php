<?php

return [

    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],
    'guards' => [
        'api' => [
            'driver' => 'session',
            'provider' => 'apiUser',
        ],
    ],
    'providers' => [
        'apiUser' => [
            'driver' => 'apiUserProvider',
        ],
    ],
];
