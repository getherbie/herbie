<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 01.01.19
 * Time: 14:31
 */

declare(strict_types=1);

namespace Herbie;

interface DataRepositoryInterface
{
    /**
     * @param string $name
     * @return array
     */
    public function load(string $name): array;

    /**
     * @return array
     */
    public function loadAll(): array;
}
