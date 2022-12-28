<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Server\MiddlewareInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class LocalExtensionsPlugin extends Plugin
{
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function consoleCommands(): array
    {
        $dir = $this->config->getAsString('plugins.LOCAL_EXT.pathConsoleCommands');
        $files = $this->findPhpFilesInDir($dir);

        $commands = [];
        foreach ($files as $file) {
            $commands[] = $this->includePhpFile($file);
        }

        return $commands;
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

    /**
     * @return mixed
     */
    private function includePhpFile(string $file)
    {
        return include $file;
    }

    public function eventListeners(): array
    {
        $dir = $this->config->getAsString('plugins.LOCAL_EXT.pathEventListeners');
        $files = $this->findPhpFilesInDir($dir);

        $events = [];
        foreach ($files as $file) {
            $events[] = $this->includePhpFile($file);
        }

        return $events;
    }

    public function applicationMiddlewares(): array
    {
        $dir = $this->config->getAsString('plugins.LOCAL_EXT.pathApplicationMiddlewares');
        $files = $this->findPhpFilesInDir($dir);

        $middlewares = [];
        foreach ($files as $file) {
            $middlewares[] = $this->includeAppMiddleware($file);
        }

        return $middlewares;
    }

    /**
     * @return MiddlewareInterface|callable|string
     */
    private function includeAppMiddleware(string $file)
    {
        return $this->includePhpFile($file);
    }

    public function routeMiddlewares(): array
    {
        $dir = $this->config->getAsString('plugins.LOCAL_EXT.pathRouteMiddlewares');
        $files = $this->findPhpFilesInDir($dir);

        $middlewares = [];
        foreach ($files as $file) {
            $middlewares[] = $this->includeRouteMiddleware($file);
        }

        return $middlewares;
    }

    /**
     * @return array{string, MiddlewareInterface|callable|string}
     */
    private function includeRouteMiddleware(string $file): array
    {
        return $this->includePhpFile($file);
    }

    public function twigFilters(): array
    {
        $dir = $this->config->getAsString('plugins.LOCAL_EXT.pathTwigFilters');
        $files = $this->findPhpFilesInDir($dir);

        $filters = [];
        foreach ($files as $file) {
            $filters[] = $this->includeTwigFilter($file);
        }

        return $filters;
    }

    /**
     * @return array{string, callable}|TwigFilter
     */
    private function includeTwigFilter(string $file)
    {
        return $this->includePhpFile($file);
    }

    public function twigGlobals(): array
    {
        $dir = $this->config->getAsString('plugins.LOCAL_EXT.pathTwigGlobals');
        $files = $this->findPhpFilesInDir($dir);

        $globals = [];
        foreach ($files as $file) {
            $globals[] = $this->includePhpFile($file);
        }

        return $globals;
    }

    public function twigFunctions(): array
    {
        $dir = $this->config->getAsString('plugins.LOCAL_EXT.pathTwigFunctions');
        $files = $this->findPhpFilesInDir($dir);

        $functions = [];
        foreach ($files as $file) {
            $functions[] = $this->includeTwigFunction($file);
        }

        return $functions;
    }

    /**
     * @return array{string, callable}|TwigFunction
     */
    private function includeTwigFunction(string $file)
    {
        return $this->includePhpFile($file);
    }

    public function twigTests(): array
    {
        $dir = $this->config->getAsString('plugins.LOCAL_EXT.pathTwigTests');
        $files = $this->findPhpFilesInDir($dir);

        $tests = [];
        foreach ($files as $file) {
            $tests[] = $this->includeTwigTests($file);
        }

        return $tests;
    }

    /**
     * @return array{string, callable}|TwigTest
     */
    private function includeTwigTests(string $file)
    {
        return $this->includePhpFile($file);
    }
}
