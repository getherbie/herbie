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
    public function __construct(string $path);
    public function load(string $name): array;
    public function loadAll(): array;
}
