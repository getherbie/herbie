<?php

return [
    'language' => 'de',
    'locale' => 'de_DE',
    'components' => [
        'dataRepository' => [
            'adapter' => 'yaml',
        ],
        'twigRenderer' => [
            'debug' => true,
            'cache' => false,
        ],
    ],
    'enabledPlugins' => 'twig,dummy,imagine,markdown,textile',
];
