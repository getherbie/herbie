<?php

declare(strict_types=1);

namespace herbie;

final class PageFactory
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

    private function isIndexPage(string $path): bool
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $filenameWithoutPrefix = preg_replace('/^([0-9])+-/', '', $filename);
        return $filenameWithoutPrefix === 'index';
    }

    public function newPageTree(PageList $pageList): PageTree
    {
        $tree = new PageTree();

        // first go through all index pages
        foreach ($pageList as $pageItem) {
            if (!$this->isIndexPage($pageItem->path)) {
                continue;
            }
            $parentRoute = $pageItem->getParentRoute();
            $parent = $tree->findByRoute($parentRoute);
            if ($parent) {
                $parent->addChild(new PageTree($pageItem));
            }
        }

        // then go through all non-index pages
        foreach ($pageList as $pageItem) {
            if ($this->isIndexPage($pageItem->path)) {
                continue;
            }
            $parentRoute = $pageItem->getParentRoute();
            $parent = $tree->findByRoute($parentRoute);
            if ($parent) {
                $parent->addChild(new PageTree($pageItem));
            }
        }

        return $tree;
    }

    public function newPageTrail(array $pageItems): PageTrail
    {
        return new PageTrail($pageItems);
    }
}
