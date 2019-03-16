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
    /**
     * @return int
     */
    public function apiVersion(): int;

    /**
     * @return array
     */
    public function events(): array;

    /**
     * @return array
     */
    public function filters(): array;

    /**
     * @return array
     */
    public function middlewares(): array;

    /**
     * @return array
     */
    public function twigFilters(): array;

    /**
     * @return array
     */
    public function twigFunctions(): array;

    /**
     * @return array
     */
    public function twigTests(): array;
}
