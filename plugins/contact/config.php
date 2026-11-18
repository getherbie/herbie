<?php

declare(strict_types=1);

return [
    'recipient' => 'me@example.com', // your email address
    'template' => null, // aliased path to custom template
    'secret' => '', // CSRF signing secret, must be configured
    'smtp' => [
        'host' => 'localhost',
        'port' => 587,
        'auth' => false,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
    ],
];
