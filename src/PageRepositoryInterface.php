<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

interface PageRepositoryInterface
{
    /**
     * @param string $id
     * @return Page|null
     */
    public function find(string $id): ?Page;

    /**
     * @return PageList
     */
    public function findAll(): PageList;

    /**
     * @param Page $page
     * @return bool
     */
    public function save(Page $page): bool;

    /**
     * @param Page $page
     * @return bool
     */
    public function delete(Page $page): bool;
}
