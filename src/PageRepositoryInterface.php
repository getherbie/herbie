<?php

/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

interface PageRepositoryInterface
{
    public function find(string $id): ?Page;

    public function findAll(): PageList;

    public function save(Page $page): bool;

    public function delete(Page $page): bool;
}
