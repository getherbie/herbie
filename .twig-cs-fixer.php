<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;
use TwigCsFixer\Config\Config;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

return (new Config('Herbie CMS'))
    ->setRuleset((new Ruleset())->addStandard(new TwigCsFixer()))
    ->setFinder(
        (new Finder())
            ->files()
            ->name('*.twig')
            ->in(__DIR__)
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs(true)
            ->notPath('vendor')
    );
