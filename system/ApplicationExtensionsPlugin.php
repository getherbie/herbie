<?php

declare(strict_types=1);

namespace herbie;

final class ApplicationExtensionsPlugin extends AbstractPlugin
{
    public function __construct(private readonly Application $application) {}

    #[\Override]
    public function consoleCommands(): array
    {
        return $this->application->getConsoleCommands();
    }

    #[\Override]
    public function eventListeners(): array
    {
        return $this->application->getEventListeners();
    }

    #[\Override]
    public function applicationMiddlewares(): array
    {
        return $this->application->getApplicationMiddlewares();
    }

    #[\Override]
    public function routeMiddlewares(): array
    {
        return $this->application->getRouteMiddlewares();
    }

    #[\Override]
    public function twigFilters(): array
    {
        return $this->application->getTwigFilters();
    }

    #[\Override]
    public function twigGlobals(): array
    {
        return $this->application->getTwigGlobals();
    }

    #[\Override]
    public function twigFunctions(): array
    {
        return $this->application->getTwigFunctions();
    }

    #[\Override]
    public function twigTests(): array
    {
        return $this->application->getTwigTests();
    }
}
