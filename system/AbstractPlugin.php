<?php

declare(strict_types=1);

namespace herbie;

abstract class AbstractPlugin implements PluginInterface
{
    #[\Override]
    public function apiVersion(): int
    {
        return 2;
    }

    #[\Override]
    public function consoleCommands(): array
    {
        return [];
    }

    #[\Override]
    public function eventListeners(): array
    {
        return [];
    }

    #[\Override]
    public function applicationMiddlewares(): array
    {
        return [];
    }

    #[\Override]
    public function routeMiddlewares(): array
    {
        return [];
    }

    #[\Override]
    public function twigFilters(): array
    {
        return [];
    }

    #[\Override]
    public function twigGlobals(): array
    {
        return [];
    }

    #[\Override]
    public function twigFunctions(): array
    {
        return [];
    }

    #[\Override]
    public function twigTests(): array
    {
        return [];
    }
}
