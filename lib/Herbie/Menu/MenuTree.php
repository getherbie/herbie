<?php

namespace Herbie\Menu;

use Iterator;

class MenuTree implements Iterator
{

    /**
     * @var array
     */
    protected $tree;

    /**
     * @param array $tree
     */
    public function __construct(array $tree = [])
    {
        $this->tree = $tree;
    }

    /**
     * @param string $route
     * @return array
     */
    public function findByRoute($route)
    {
        return $this->doFindByRoute($this->tree, $route);
    }

    /**
     * @param array $tree
     * @param string $route
     * @return array
     */
    protected function doFindByRoute($tree, $route)
    {
        foreach($tree AS $item) {
            if($item->getRoute() == $route) {
                return $item->getItems();
            }
            if($item->hasItems()) {
                $this->doFindByRoute($item->getItems(), $route);
            }
        }
        return [];
    }

    /**
     * @return void
     */
    public function rewind()
    {
        reset($this->tree);
    }

    /**
     * @return MenuItem
     */
    public function current()
    {
        return current($this->tree);
    }

    /**
     * @return string
     */
    public function key()
    {
        return key($this->tree);
    }

    /**
     * @return void
     */
    public function next()
    {
        next($this->tree);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return key($this->tree) !== null;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'MenuTree could not be converted to string.';
    }

}