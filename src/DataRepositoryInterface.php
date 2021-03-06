<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

interface DataRepositoryInterface
{
    /**
     * DataRepositoryInterface constructor.
     * @param string $path
     */
    public function __construct(string $path);

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
