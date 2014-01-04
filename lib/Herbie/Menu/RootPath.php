<?php

namespace Herbie\Menu;

use IteratorAggregate;
use Countable;
use ArrayIterator;

class RootPath implements IteratorAggregate, Countable
{

    protected $collection;
    protected $route;
    protected $items;

    public function __construct($collection, $route)
    {
        $this->collection = $collection;
        $this->route = $route;
        $this->items = $this->buildRootPath();
    }

    protected function buildRootPath()
    {
        $items = [];

        $segments = explode('/', rtrim($this->route, '/'));
        $route = '';
        $delim = '';
        foreach($segments AS $segment) {
            $route .= $delim . $segment;
            $delim = '/';

            $item = $this->collection->getItem($route);
            if(isset($item)) {
                $items[] = $item;
            }
        }

        return $items;
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
        return 'RootPath could not be converted to string.';
    }

}
