<?php

declare(strict_types=1);

return [
    'recipient' => 'me@example.com', // your email address
    'template' => null, // aliased path to custom template
    'honeypots' => ['phone', 'website'], // hidden fields that must stay empty
    'csrf' => [
        'secret' => '', // signing secret, must be configured
        'tokenTtlSeconds' => 900, // token lifetime (60..3600)
    ],
    'captcha' => [
        'secret' => '', // ALTCHA HMAC secret, must be configured
        'cost' => 5000, // PBKDF2 iterations (1000..100000)
        'ttlSeconds' => 900, // challenge lifetime (60..3600)
    ],
    'form' => [
        'minAgeSeconds' => 3, // minimum time before a form can be submitted (0..60)
        'maxAgeSeconds' => 900, // maximum time before a form can be submitted (60..3600)
    ],
    'rateLimit' => [
        'maxRequests' => 5, // max submissions per IP and window (1..100)
        'windowSeconds' => 3600, // rate limit window (60..86400)
    ],
    'smtp' => [
        'host' => 'localhost',
        'port' => 587,
        'auth' => false,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
    ],
];
