<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Server\MiddlewareInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class VirtualLocalPlugin extends Plugin
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function commands(): array
    {
        $dir = $this->config->getAsString('paths.site') . '/extend/commands';
        $files = $this->findPhpFilesInDir($dir);

        $commands = [];
        foreach ($files as $file) {
            $commands[] = $this->includePhpFile($file);
        }

        return $commands;
    }

    public function events(): array
    {
        $dir = $this->config->getAsString('paths.site') . '/extend/events';
        $files = $this->findPhpFilesInDir($dir);

        $events = [];
        foreach ($files as $file) {
            $events[] = $this->includePhpFile($file);
        }

        return $events;
    }

    public function filters(): array
    {
        $dir = $this->config->getAsString('paths.site') . '/extend/filters';
        $files = $this->findPhpFilesInDir($dir);

        $filters = [];
        foreach ($files as $file) {
            $filters[] = $this->includePhpFile($file);
        }

        return $filters;
    }

    public function appMiddlewares(): array
    {
        $dir = $this->config->getAsString('paths.site') . '/extend/middlewares_app';
        $files = $this->findPhpFilesInDir($dir);

        $middlewares = [];
        foreach ($files as $file) {
            $middlewares[] = $this->includeAppMiddleware($file);
        }

        return $middlewares;
    }

    public function routeMiddlewares(): array
    {
        $dir = $this->config->getAsString('paths.site') . '/extend/middlewares_route';
        $files = $this->findPhpFilesInDir($dir);

        $middlewares = [];
        foreach ($files as $file) {
            $middlewares[] = $this->includeRouteMiddleware($file);
        }

        return $middlewares;
    }

    public function twigFilters(): array
    {
        $dir = $this->config->getAsString('paths.twigFilters');
        $files = $this->findPhpFilesInDir($dir);

        $filters = [];
        foreach ($files as $file) {
            $filters[] = $this->includeTwigFilter($file);
        }

        return $filters;
    }

    public function twigGlobals(): array
    {
        $dir = $this->config->getAsString('paths.twigGlobals');
        $files = $this->findPhpFilesInDir($dir);

        $globals = [];
        foreach ($files as $file) {
            $globals = array_merge($globals, $this->includePhpFile($file));
        }

        return $globals;
    }

    public function twigFunctions(): array
    {
        $dir = $this->config->getAsString('paths.twigFunctions');
        $files = $this->findPhpFilesInDir($dir);

        $functions = [];
        foreach ($files as $file) {
            $functions[] = $this->includeTwigFunction($file);
        }

        return $functions;
    }

    public function twigTests(): array
    {
        $dir = $this->config->getAsString('paths.twigTests');
        $files = $this->findPhpFilesInDir($dir);

        $tests = [];
        foreach ($files as $file) {
            $tests[] = $this->includeTwigTests($file);
        }

        return $tests;
    }

    /**
     * @return MiddlewareInterface|callable|string
     */
    private function includeAppMiddleware(string $file)
    {
        return $this->includePhpFile($file);
    }

    /**
     * @return array{string, MiddlewareInterface|callable|string}
     */
    private function includeRouteMiddleware(string $file): array
    {
        return $this->includePhpFile($file);
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

    /**
     * @return string[]
     */
    private function findPhpFilesInDir(string $dir): array
    {
        $dir = str_untrailing_slash($dir);
        if (empty($dir) || !is_readable($dir)) {
            return [];
        }
        $pattern = $dir . '/*.php';
        $files = glob($pattern);
        if ($files === false) {
            return [];
        }
        return $files;
    }
}
