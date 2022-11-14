<?php

declare(strict_types=1);

namespace herbie;

final class ApplicationExtensionsPlugin extends Plugin
{
    private Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function consoleCommands(): array
    {
        return $this->application->getConsoleCommands();
    }

    public function eventListeners(): array
    {
        return $this->application->getEventListeners();
    }

    public function interceptingFilters(): array
    {
        return $this->application->getInterceptingFilters();
    }

    public function applicationMiddlewares(): array
    {
        return $this->application->getApplicationMiddlewares();
    }

    public function routeMiddlewares(): array
    {
        return $this->application->getRouteMiddlewares();
    }

    public function twigFilters(): array
    {
        return $this->application->getTwigFilters();
    }

    public function twigGlobals(): array
    {
        return $this->application->getTwigGlobals();
    }

    public function twigFunctions(): array
    {
        return $this->application->getTwigFunctions();
    }

    public function twigTests(): array
    {
        return $this->application->getTwigTests();
    }
}
