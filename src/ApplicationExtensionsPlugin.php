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

    public function commands(): array
    {
        return $this->application->getCommands();
    }

    public function events(): array
    {
        return $this->application->getEvents();
    }

    public function filters(): array
    {
        return $this->application->getFilters();
    }

    public function appMiddlewares(): array
    {
        return $this->application->getAppMiddlewares();
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
