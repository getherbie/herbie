<?php

namespace Herbie\Menu;

use IteratorAggregate;
use Countable;
use ArrayIterator;

class MenuCollection implements IteratorAggregate, Countable
{

    /**
     * @var array
     */
    protected $items;

    /**
     * @param string $route
     * @param MenuItem $item
     */
    public function addItem(MenuItem $item)
    {
        $route = $item->getRoute();
        $this->items[$route] = $item;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param type $route
     * @return MenuItem|null
     */
    public function getItem($route)
    {
        return isset($this->items[$route]) ? $this->items[$route] : NULL;
    }

    /**
     * @return ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'MenuCollection could not be converted to string.';
    }

}
