<?php

declare(strict_types=1);

namespace herbie;

use Psr\Http\Server\MiddlewareInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

final class LocalExtensionsPlugin extends Plugin
{
    protected Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function consoleCommands(): array
    {
        $dir = $this->application->getConfig()->getAsString('plugins.LOCAL_EXT.pathConsoleCommands');
        $files = $this->findPhpFilesInDir($dir);

        $commands = [];
        foreach ($files as $file) {
            $commands[] = $this->includePhpFile($file);
        }

        return $commands;
    }

    public function eventListeners(): array
    {
        $dir = $this->application->getConfig()->getAsString('plugins.LOCAL_EXT.pathEventListeners');
        $files = $this->findPhpFilesInDir($dir);

        $events = [];
        foreach ($files as $file) {
            $events[] = $this->includePhpFile($file);
        }

        return $events;
    }

    public function applicationMiddlewares(): array
    {
        $dir = $this->application->getConfig()->getAsString('plugins.LOCAL_EXT.pathApplicationMiddlewares');
        $files = $this->findPhpFilesInDir($dir);

        $middlewares = [];
        foreach ($files as $file) {
            $middlewares[] = $this->includeAppMiddleware($file);
        }

        return $middlewares;
    }

    public function routeMiddlewares(): array
    {
        $dir = $this->application->getConfig()->getAsString('plugins.LOCAL_EXT.pathRouteMiddlewares');
        $files = $this->findPhpFilesInDir($dir);

        $middlewares = [];
        foreach ($files as $file) {
            $middlewares[] = $this->includeRouteMiddleware($file);
        }

        return $middlewares;
    }

    public function twigFilters(): array
    {
        $dir = $this->application->getConfig()->getAsString('plugins.LOCAL_EXT.pathTwigFilters');
        $files = $this->findPhpFilesInDir($dir);

        $filters = [];
        foreach ($files as $file) {
            $filters[] = $this->includeTwigFilter($file);
        }

        return $filters;
    }

    public function twigGlobals(): array
    {
        $dir = $this->application->getConfig()->getAsString('plugins.LOCAL_EXT.pathTwigGlobals');
        $files = $this->findPhpFilesInDir($dir);

        $globals = [];
        foreach ($files as $file) {
            $globals[] = $this->includePhpFile($file);
        }

        return $globals;
    }

    public function twigFunctions(): array
    {
        $dir = $this->application->getConfig()->getAsString('plugins.LOCAL_EXT.pathTwigFunctions');
        $files = $this->findPhpFilesInDir($dir);

        $functions = [];
        foreach ($files as $file) {
            $functions[] = $this->includeTwigFunction($file);
        }

        return $functions;
    }

    public function twigTests(): array
    {
        $dir = $this->application->getConfig()->getAsString('plugins.LOCAL_EXT.pathTwigTests');
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

    /**
     * @return array{string, callable}|TwigFilter
     */
    private function includeTwigFilter(string $file)
    {
        return $this->includePhpFile($file);
    }

    /**
     * @return array{string, callable}|TwigFunction
     */
    private function includeTwigFunction(string $file)
    {
        return $this->includePhpFile($file);
    }

    /**
     * @return array{string, callable}|TwigTest
     */
    private function includeTwigTests(string $file)
    {
        return $this->includePhpFile($file);
    }

    /**
     * @return mixed
     */
    private function includePhpFile(string $file)
    {
        return include $file;
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
