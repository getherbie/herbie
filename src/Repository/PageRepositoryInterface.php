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
use Herbie\Page\PageList;
use Herbie\Page\PageTrail;
use Herbie\Page\PageTree;

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
     * @return PageTree
     */
    public function buildTree(): PageTree;

    /**
     * @return PageTrail
     */
    public function buildTrail(): PageTrail;

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
