<?php

return [
    'components' => [
        'fileCache' => null,
        'fileLogger' => null,
        'twigRenderer' => [
            'debug' => true,
        ],
    ],
    'enabledPlugins' => 'twig,markdown',
    'plugins' => [
        'markdown' => [
            'enableTwigFilter' => false,
            'enableTwigFunction' => false,
        ],
    ],
];
