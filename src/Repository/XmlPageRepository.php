<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 01.01.19
 * Time: 13:36
 */

namespace Herbie\Repository;

use Herbie\Page;

class XmlPageRepository implements PageRepositoryInterface
{

    /**
     * @param string $id
     * @return Page|null
     */
    public function find(string $id): ?Page
    {
        // TODO: Implement find() method.
    }

    /**
     * @return array
     */
    public function findAll(): array
    {
        // TODO: Implement findAll() method.
    }

    /**
     * @param Page $page
     * @return bool
     */
    public function save(Page $page): bool
    {
        // TODO: Implement save() method.
    }

    /**
     * @param Page $page
     * @return bool
     */
    public function delete(Page $page): bool
    {
        // TODO: Implement delete() method.
    }
}
