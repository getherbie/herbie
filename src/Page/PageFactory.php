<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-01-18
 * Time: 06:53
 */

declare(strict_types=1);

namespace Herbie\Page;

use Herbie\Environment;

class PageFactory
{
    /**
     * @param string $id
     * @param string $parent
     * @param array $data
     * @param array $segments
     * @return Page
     */
    public function newPage(string $id, string $parent, array $data, array $segments): Page
    {
        $page = new Page();
        $page->setId($id);
        $page->setParent($parent);
        $page->setData($data);
        $page->setSegments($segments);
        return $page;
    }

    /**
     * @param array $data
     * @return PageItem
     */
    public function newPageItem(array $data = [])
    {
        return new PageItem($data);
    }

    /**
     * @param array $items
     * @return PageList
     */
    public function newPageList(array $items = [])
    {
        return new PageList($items);
    }

    /**
     * @param PageList $pageList
     * @return PageTree
     */
    public function newPageTree(PageList $pageList): PageTree
    {
        $tree = new PageTree();
        foreach ($pageList as $pageItem) {
            $route = $pageItem->getParentRoute();
            $node = $tree->findByRoute($route);
            if ($node) {
                $node->addChild(new PageTree($pageItem));
            }
        }
        return $tree;
    }

    /**
     * @param $pageItems
     * @return PageTrail
     */
    public function newPageTrail($pageItems): PageTrail
    {
        return new PageTrail($pageItems);
    }
}
