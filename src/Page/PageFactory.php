<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 2019-01-18
 * Time: 06:53
 */

declare(strict_types=1);

namespace Herbie\Page;

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

    public function newPageItem(array $data = [])
    {
        return new PageItem($data);
    }

    public function newPageList(array $items = [])
    {
        return new PageList($items);
    }
}
