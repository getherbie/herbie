<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 01.01.19
 * Time: 11:30
 */

namespace Herbie;

class PageFactory
{

    /**
     * @param string $id
     * @param string $parent
     * @param array $data
     * @param array $segments
     * @return Page
     */
    public function __invoke(string $id, string $parent, array $data, array $segments) : Page
    {
        $page = new Page();
        $page->setId($id);
        $page->setParent($parent);
        $page->setData($data);
        $page->setSegments($segments);
        return $page;
    }

}
