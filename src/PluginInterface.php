<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-01-07
 * Time: 19:53
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
