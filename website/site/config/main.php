<?php

$isProduction = isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME'] !== 'localhost');

return [
    'language' => 'en',
    'locale' => 'en_EN',
    'theme' => 'default',
    'fileExtensions' => [
        'layouts' => 'twig'
    ],
    'enabledPlugins' => 'simplesearch,simplecontact',
    'enabledSysPlugins' => 'twig,imagine,markdown',
    'components' => [
        'dataRepository' => [
            'adapter' => 'yaml'
        ],
        'fileLogger' => [
            'level' => $isProduction ? 'error' : 'debug',
        ],
        'flatFilePagePersistence' => [
            'cache' => $isProduction
        ],
        'pageRendererMiddleware' => [
            'cache' => $isProduction
        ],
        'twigRenderer' => [
            'debug' => !$isProduction
        ],
        'urlManager' => [
            'niceUrls' => $isProduction,
            'rules' => [
                ['recipes/category/{category}', 'recipes'],
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
            ]
        ]
    ],
    'plugins' => [
        'simplecontact' => [
            'config' => [
                'recipient' => 'me+herbie@tebe.ch'
            ]
        ],
        'simplesearch' => [
            'config' => [
                'usePageCache' => $isProduction
            ]
        ],
    ]
];
