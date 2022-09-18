<?php

return [
    'language' => 'en',
    'locale' => 'en_EN',
    'theme' => 'default',
    'niceUrls' => true,
    'fileExtensions' => [
        'layouts' => 'twig'
    ],
    'twig' => [
        'debug' => true,
        'strictVariables' => false,
    ],
    'enabledPlugins' => 'simplecontact,simplesearch',
    'enabledSysPlugins' => 'twig_core,dummy,imagine,markdown,textile',
    'components' => [
        'urlMatcher' => [
            'rules' => [
                ['blog/author/{author}', 'blog'],
                ['blog/category/{category}', 'blog'],
                ['blog/tag/{tag}', 'blog'],
                ['blog/{year}/{month}/{day}', 'blog', ['year' => '[0-9]{4}', 'month' => '[0-9]{2}', 'day' => '[0-9]{2}']],
                ['blog/{year}/{month}', 'blog', ['year' => '[0-9]{4}', 'month' => '[0-9]{2}']],
                ['blog/{year}', 'blog', ['year' => '[0-9]{4}']]
            ]
        ]
    ],
];
