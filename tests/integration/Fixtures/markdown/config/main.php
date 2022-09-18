<?php

return [
    'twig' => [
        'debug' => true,
    ],
    'enabledSysPlugins' => 'twig_core,markdown',
    'plugins' => [
        'markdown' => [
            'enableTwigFilter' => false,
            'enableTwigFunction' => false
        ]
    ]
];
