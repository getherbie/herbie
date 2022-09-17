<?php

return [
    'twig' => [
        'debug' => true,
    ],
    'enabledSysPlugins' => 'markdown',
    'plugins' => [
        'markdown' => [
            'enableTwigFilter' => false,
            'enableTwigFunction' => false
        ]
    ]
];
