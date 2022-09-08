<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

class PageFactory
{
    public function newPage(string $id, string $parent, array $data, array $segments): Page
    {
        $page = new Page();
        $page->setId($id);
        $page->setParent($parent);
        $page->setData($data);
        $page->setSegments($segments);
        return $page;
    }

    public function newPageItem(array $data = []): PageItem
    {
        return new PageItem($data);
    }

    public function newPageList(array $items = []): PageList
    {
        return new PageList($items);
    }

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

    public function newPageTrail(array $pageItems): PageTrail
    {
        return new PageTrail($pageItems);
    }
}
