<?php

return [
    'language' => 'en',
    'locale' => 'en_EN',
    'theme' => 'default',
    'fileExtensions' => [
        'layouts' => 'twig'
    ],
    'enabledPlugins' => 'simplesearch',
    'enabledSysPlugins' => 'twig_core,twig_plus,imagine,markdown',
    'components' => [
        'dataRepository' => [
            'adapter' => 'yaml'
        ],
        'fileLogger' => [
            'level' => 'debug',
        ],
        'pageRendererMiddleware' => [
            'cache' => isset($_SERVER['SERVER_NAME']) && ($_SERVER['SERVER_NAME'] !== 'localhost')
        ],
        'twigRenderer' => [
            'debug' => true
        ],
        'urlManager' => [
            'niceUrls' => true,
            'rules' => [
                ['blog/author/{author}', 'blog'],
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
                ]]
            ]
        ]
    ],
    'plugins' => [
        'simplesearch' => [
            'usePageCache' => true
        ]
    ]
];
