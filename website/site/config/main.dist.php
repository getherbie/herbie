<?php

$isProduction = isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME'] === 'herbie.tebe.ch');

return [
    'language' => 'en',
    'locale' => 'en_EN',
    'theme' => 'default',
    'fileExtensions' => [
        'layouts' => 'twig',
    ],
    'enabledPlugins' => 'twig,imagine,markdown,search,contact',
    'components' => [
        'dataRepository' => [
            'adapter' => 'yaml',
        ],
        'fileLogger' => [
            'level' => $isProduction ? 'error' : 'debug',
        ],
        'flatFilePagePersistence' => [
            'cache' => $isProduction,
        ],
        'pageRendererMiddleware' => [
            'cache' => $isProduction,
        ],
        'twigRenderer' => [
            'debug' => !$isProduction,
        ],
        'urlManager' => [
            'niceUrls' => $isProduction,
            'rules' => [
                /*['blog/author/{author}', 'blog'],
                ['blog/category/{category}', 'blog'],
                ['blog/tag/{tag}', 'blog'],
                ['blog/{year}/{month}/{day}', 'blog', [
                    'year' => '[0-9]{4}',
                    'month' => '[0-9]{2}',
                    'day' => '[0-9]{2}'
                ]],
                ['blog/{year}/{month}', 'blog', [
                    'year' => '[0-9]{4}',
                    'month' => '[0-9]{2}'
                ]],
                ['blog/{year}', 'blog', [
                    'year' => '[0-9]{4}'
                ]]*/
            ],
        ],
    ],
    'plugins' => [
        'contact' => [
            'recipient' => '<plugin_contact_recipient>',
            'csrf' => [
                'secret' => '<plugin_contact_csrf_secret>',
            ],
            'captcha' => [
                'secret' => '<plugin_contact_captcha_secret>',
            ],
            'smtp' => [
                'host' => '<plugin_contact_smtp_host>',
                'port' => 587,
                'auth' => true,
                'username' => '<plugin_contact_smtp_username>',
                'password' => '<plugin_contact_smtp_password>',
                'encryption' => 'tls',
            ],
        ],
        'search' => [
            'useCache' => $isProduction,
        ],
    ],
];
