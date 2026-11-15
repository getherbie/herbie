<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/config',
        __DIR__ . '/messages',
        __DIR__ . '/plugins',
        __DIR__ . '/system',
        __DIR__ . '/website',
    ]);

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@PER-CS3x0' => true,
    //'strict_param' => true,
    'array_syntax' => ['syntax' => 'short'],
])->setFinder($finder);
