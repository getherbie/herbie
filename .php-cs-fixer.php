<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/config',
        __DIR__ . '/messages',
        __DIR__ . '/src',
        __DIR__ . '/sysplugins',
        __DIR__ . '/website',
    ]);

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PSR12' => true,
    //'strict_param' => true,
    'array_syntax' => ['syntax' => 'short'],
])->setFinder($finder);
