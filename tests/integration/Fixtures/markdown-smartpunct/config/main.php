<?php

return [
    'components' => [
        'fileCache' => null,
        'fileLogger' => null,
        'twigRenderer' => [
            'debug' => true,
            'cache' => false,
        ],
    ],
    'enabledPlugins' => 'twig,markdown',
    'plugins' => [
        'markdown' => [
            'extensions' => [
                \League\CommonMark\Extension\SmartPunct\SmartPunctExtension::class,
            ],
        ],
    ],
];
