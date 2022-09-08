<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

abstract class Plugin implements PluginInterface
{
    public function apiVersion(): int
    {
        return 2;
    }

    public function events(): array
    {
        return [];
    }

    public function filters(): array
    {
        return [];
    }

    public function middlewares(): array
    {
        return [];
    }

    public function twigFilters(): array
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
