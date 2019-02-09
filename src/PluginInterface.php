<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

interface PluginInterface
{
    public function attach(): void;

    /**
     * @return array
     */
    public function getEvents(): array;

    /**
     * @return array
     */
    public function getFilters(): array;

    /**
     * @return array
     */
    public function getMiddlewares(): array;

    /**
     * @return array
     */
    public function getTwigFilters(): array;

    /**
     * @return array
     */
    public function getTwigFunctions(): array;

    /**
     * @return array
     */
    public function getTwigTests(): array;
}
