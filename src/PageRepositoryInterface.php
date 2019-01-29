<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 31.12.18
 * Time: 15:14
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
