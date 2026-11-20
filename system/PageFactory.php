<?php

declare(strict_types=1);

namespace herbie;

use Ausi\SlugGenerator\SlugGenerator;

final readonly class PageFactory
{
    public function __construct(private ?SlugGenerator $slugGenerator = null) {}

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
        $pages = iterator_to_array($pageList);

        // A page can be listed before its parent. Attach it only when the parent
        // node exists, so the order of the page list is preserved.
        while ($pages !== []) {
            $attached = false;
            foreach ($pages as $key => $page) {
                $parent = $tree->findByRoute($page->getParentRoute());
                if ($parent === null) {
                    continue;
                }
                $parent->addChild(new PageTree($page));
                unset($pages[$key]);
                $attached = true;
            }
            if (!$attached) {
                break; // pages without an existing parent are ignored
            }
        }

        return $tree;
    }

    public function newPageTrail(array $pages): PageTrail
    {
        return new PageTrail($pages);
    }
}
