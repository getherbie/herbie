<?php

return [
    'language' => 'de',
    'locale' => 'de_DE',
    'components' => [
        'twigRenderer' => [
            'debug' => true,
            'cache' => false,
        ],
    ],
    'enabledSysPlugins' => 'twig_core,twig_plus,dummy,imagine,markdown,textile',
];
