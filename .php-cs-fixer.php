<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->ignoreVCSIgnored(true)
    ->append([__DIR__ . '/herbie']);

$config = new PhpCsFixer\Config();
return $config
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRules([
        '@PER-CS3x0' => true,
        'cast_spaces' => ['space' => 'none'],
    ])
    ->setFinder($finder);
