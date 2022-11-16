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
    '@PSR12' => true,
    //'strict_param' => true,
    'array_syntax' => ['syntax' => 'short'],
])->setFinder($finder);
