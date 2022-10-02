<?php

declare(strict_types=1);

namespace herbie;

interface PluginInterface
{
    public function apiVersion(): int;

    public function events(): array;

    public function filters(): array;

    public function middlewares(): array;

    public function twigFilters(): array;

    public function twigFunctions(): array;

    public function twigTests(): array;
}
