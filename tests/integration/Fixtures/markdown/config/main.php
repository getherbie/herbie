<?php

return [
    'components' => [
        'fileCache' => null,
        'fileLogger' => null,
        'twigRenderer' => [
            'debug' => true,
        ],
    ],
    'enabledSysPlugins' => 'twig_core,twig_plus,markdown',
    'plugins' => [
        'markdown' => [
            'enableTwigFilter' => false,
            'enableTwigFunction' => false
        ]
    ]
];
