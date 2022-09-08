<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

interface PagePersistenceInterface
{
    public function findById(string $id): array;

    public function findAll(): array;
}
