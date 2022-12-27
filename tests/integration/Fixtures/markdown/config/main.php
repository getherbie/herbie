<?php

return [
    'components' => [
        'fileCache' => null,
        'fileLogger' => null,
        'twigRenderer' => [
            'debug' => true,
        ],
    ],
    'enabledSysPlugins' => 'twig,markdown',
    'plugins' => [
        'markdown' => [
            'enableTwigFilter' => false,
            'enableTwigFunction' => false
        ]
    ]
];
