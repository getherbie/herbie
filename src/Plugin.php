<?php

declare(strict_types=1);

namespace Herbie;

abstract class Plugin implements PluginInterface
{

    public function attach(): void
    {
    }

    /**
     * @return array
     */
    public function getEvents(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getMiddlewares(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getTwigFilters(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getTwigFunctions(): array
    {
        return [];
    }

    /**
     * @return array
     */
    public function getTwigTests(): array
    {
        return [];
    }
}
