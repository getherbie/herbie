<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 31.12.18
 * Time: 15:14
 */

declare(strict_types=1);

namespace Herbie\Repository;

use Herbie\Page\Page;

interface PageRepositoryInterface
{
    /**
     * @param string $id
     * @return Page|null
     */
    public function find(string $id): ?Page;

    /**
     * @return array
     */
    public function findAll(): array;

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
