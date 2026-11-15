<?php

declare(strict_types=1);

namespace herbie;

use Ausi\SlugGenerator\SlugGenerator;

final readonly class PageFactory
{
    private ?SlugGenerator $slugGenerator;

    public function __construct(?SlugGenerator $slugGenerator = null)
    {
        $this->slugGenerator = $slugGenerator;
    }

    public function newPage(array $data, array $segments): Page
    {
        return new Page($data, $segments, $this->slugGenerator);
    }

    public function newPageItem(array $data = []): Page
    {
        return new Page($data, null, $this->slugGenerator);
    }

    public function newPageList(array $items = []): PageList
    {
        return new PageList($items);
    }

    public function newPageTree(PageList $pageList): PageTree
    {
        $tree = new PageTree();

        // first go through all index pages
        foreach ($pageList as $page) {
            if (!$this->isIndexPage($page->getPath())) {
                continue;
            }
            $parentRoute = $page->getParentRoute();
            $parent = $tree->findByRoute($parentRoute);
            if ($parent) {
                $parent->addChild(new PageTree($page));
            }
        }

        // then go through all non-index pages
        foreach ($pageList as $page) {
            if ($this->isIndexPage($page->getPath())) {
                continue;
            }
            $parentRoute = $page->getParentRoute();
            $parent = $tree->findByRoute($parentRoute);
            if ($parent) {
                $parent->addChild(new PageTree($page));
            }
        }

        return $tree;
    }

    private function isIndexPage(string $path): bool
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $filenameWithoutPrefix = preg_replace('/^([0-9])+-/', '', $filename);
        return $filenameWithoutPrefix === 'index';
    }

    public function newPageTrail(array $pages): PageTrail
    {
        return new PageTrail($pages);
    }
}
