<?php

/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
