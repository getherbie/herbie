<?php

return [
    'language' => 'en',
    'locale' => 'en_EN',
    'enabledPlugins' => 'twig,imagine,markdown,rst,textile,search,contact',
    'components' => [
        'dataRepository' => [
            'adapter' => 'yaml',
        ],
        'twigRenderer' => [
            'debug' => true,
            'cache' => false,
        ],
    ],
    'plugins' => [
        'contact' => [
            'recipient' => 'dev@example.com',
        ],
    ],
];
