<?php

declare(strict_types=1);

namespace herbie;

final class PageFactory
{
    public function newPage(array $data, array $segments): Page
    {
        return new Page($data, $segments);
    }

    public function newMenuItem(array $data = []): MenuItem
    {
        return new MenuItem($data);
    }

    public function newMenuList(array $items = []): MenuList
    {
        return new MenuList($items);
    }

    private function isIndexPage(string $path): bool
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $filenameWithoutPrefix = preg_replace('/^([0-9])+-/', '', $filename);
        return $filenameWithoutPrefix === 'index';
    }

    public function newMenuTree(MenuList $menuList): MenuTree
    {
        $tree = new MenuTree();

        // first go through all index pages
        foreach ($menuList as $pageItem) {
            if (!$this->isIndexPage($pageItem->path)) {
                continue;
            }
            $parentRoute = $pageItem->getParentRoute();
            $parent = $tree->findByRoute($parentRoute);
            if ($parent) {
                $parent->addChild(new MenuTree($pageItem));
            }
        }

        // then go through all non-index pages
        foreach ($menuList as $pageItem) {
            if ($this->isIndexPage($pageItem->path)) {
                continue;
            }
            $parentRoute = $pageItem->getParentRoute();
            $parent = $tree->findByRoute($parentRoute);
            if ($parent) {
                $parent->addChild(new MenuTree($pageItem));
            }
        }

        return $tree;
    }

    public function newMenuTrail(array $pageItems): MenuTrail
    {
        return new MenuTrail($pageItems);
    }
}
