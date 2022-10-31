<?php

return [
    'enabledSysPlugins' => 'twig_core,twig_plus,textile',
    'components' => [
        'fileLogger' => null,
    ],
    'plugins' => [
        'textile' => [
            'enableTwigFilter' => false,
            'enableTwigFunction' => false
        ]
    ]
];
