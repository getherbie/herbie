<?php

declare(strict_types=1);

namespace herbie;

abstract class Plugin implements PluginInterface
{
    public function apiVersion(): int
    {
        return 2;
    }

    public function consoleCommands(): array
    {
        return [];
    }

    public function eventListeners(): array
    {
        return [];
    }

    public function applicationMiddlewares(): array
    {
        return [];
    }

    public function routeMiddlewares(): array
    {
        return [];
    }

    public function twigFilters(): array
    {
        return [];
    }

    public function twigGlobals(): array
    {
        return [];
    }

    public function twigFunctions(): array
    {
        return [];
    }

    public function twigTests(): array
    {
        return [];
    }
}
