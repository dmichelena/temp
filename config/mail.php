<?php


    /*
    |--------------------------------------------------------------------------
    | Mail Driver
    |--------------------------------------------------------------------------
    |
    | Laravel supports both SMTP and PHP's "mail" function as drivers for the
    | sending of e-mail. You may specify which one you're using throughout
    | your application here. By default, Laravel is setup for SMTP mail.
    |
    | Supported: "smtp", "mail", "sendmail", "mailgun", "mandrill",
    |            "ses", "sparkpost", "log"
    |
 */
return [
    'driver' => 'smtp',
    'host' => 'smtp.mailtrap.io',
    'port' => 2525,
    'from' => [
        'address' => 'multicinesec@mc.ec',
        'name' => 'MultiCines',
    ],
    'encryption' => 'tls',
    'username' => '83637185021649',
    'password' => '3b6f512d26e83f',

    'sendmail' => '/usr/sbin/sendmail -bs',

];