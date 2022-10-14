<?php

declare(strict_types=1);

namespace herbie;

interface PluginInterface
{
    public function apiVersion(): int;

    public function events(): array;

    public function filters(): array;

    public function appMiddlewares(): array;

    public function routeMiddlewares(): array;

    public function twigFilters(): array;

    public function twigGlobals(): array;

    public function twigFunctions(): array;

    public function twigTests(): array;
}
