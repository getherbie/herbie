<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 01.01.19
 * Time: 13:36
 */

declare(strict_types=1);

namespace Herbie\Repository;

use Herbie\Page\Page;
use Herbie\Page\PageList;

class XmlPageRepository implements PageRepositoryInterface
{

    /**
     * @param string $id
     * @return Page|null
     */
    public function find(string $id): ?Page
    {
        // TODO: Implement find() method.
        return null;
    }

    /**
     * @return PageList
     */
    public function findAll(): PageList
    {
        // TODO: Implement findAll() method.
        return [];
    }

    /**
     * @param Page $page
     * @return bool
     */
    public function save(Page $page): bool
    {
        // TODO: Implement save() method.
        return false;
    }

    /**
     * @param Page $page
     * @return bool
     */
    public function delete(Page $page): bool
    {
        // TODO: Implement delete() method.
        return false;
    }
}
