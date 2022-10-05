<?php

declare(strict_types=1);

namespace herbie;

use Psr\Log\LoggerInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class VirtualLocalPlugin extends Plugin
{
    private LoggerInterface $logger;
    private Config $config;

    public function __construct(LoggerInterface $logger, Config $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    public function events(): array
    {
        $dir = $this->config->getAsString('paths.site') . '/extend/events';
        $files = $this->globPhpFiles($dir);

        $events = [];
        foreach ($files as $file) {
            $events[] = $this->includePhpFile($file);
        }

        return $events;
    }

    public function filters(): array
    {
        $dir = $this->config->getAsString('paths.site') . '/extend/filters';
        $files = $this->globPhpFiles($dir);

        $filters = [];
        foreach ($files as $file) {
            $filters[] = $this->includePhpFile($file);
        }

        return $filters;
    }

    public function middlewares(): array
    {
        return []; // TODO
    }

    public function twigFilters(): array
    {
        $dir = $this->config->getAsString('twig.filtersPath');
        $files = $this->globPhpFiles($dir);

        $filters = [];
        foreach ($files as $file) {
            $filters[] = $this->includeTwigFilter($file);
        }

        return $filters;
    }

    public function twigFunctions(): array
    {
        $dir = $this->config->getAsString('twig.functionsPath');
        $files = $this->globPhpFiles($dir);

        $functions = [];
        foreach ($files as $file) {
            $functions[] = $this->includeTwigFunction($file);
        }

        return $functions;
    }

    public function twigTests(): array
    {
        $dir = $this->config->getAsString('twig.testsPath');
        $files = $this->globPhpFiles($dir);

        $tests = [];
        foreach ($files as $file) {
            $tests[] = $this->includeTwigTests($file);
        }

        return $tests;
    }

    private function includeTwigFilter(string $file): TwigFilter
    {
        return $this->includePhpFile($file);
    }

    private function includeTwigFunction(string $file): TwigFunction
    {
        return $this->includePhpFile($file);
    }

    private function includeTwigTests(string $file): TwigTest
    {
        return $this->includePhpFile($file);
    }

    /**
     * @return mixed
     */
    private function includePhpFile(string $file)
    {
        return include($file);
    }

    private function globPhpFiles(string $dir): array
    {
        $dir = rtrim($dir, '/');
        if (empty($dir) || !is_readable($dir)) {
            return [];
        }
        $pattern = $dir . '/*.php';
        return glob($pattern);
    }
}
