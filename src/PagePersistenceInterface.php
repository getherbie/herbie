<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

interface PagePersistenceInterface
{
    /**
     * @param string $id
     * @return array
     */
    public function findById(string $id): array;

    /**
     * @return array
     */
    public function findAll(): array;
}
