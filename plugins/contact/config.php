<?php

declare(strict_types=1);

return [
    'recipient' => 'me@example.com', // your email address
    'template' => null, // aliased path to custom template
    'secret' => 'change-this-to-a-random-secret', // CSRF signing secret
    'smtp' => [
        'host' => 'localhost',
        'port' => 587,
        'auth' => false,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
    ],
];
