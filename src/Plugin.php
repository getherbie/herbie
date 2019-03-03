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
