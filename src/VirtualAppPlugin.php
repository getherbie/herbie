<?php

declare(strict_types=1);

namespace herbie;

final class VirtualAppPlugin extends Plugin
{
    private Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
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
        return []; // TODO
    }

    public function routeMiddlewares(): array
    {
        return []; // TODO
    }

    public function twigFilters(): array
    {
        return $this->application->getTwigFilters();
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
