<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Page;

class Collection implements \IteratorAggregate, \Countable
{

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @param string $route
     * @param Item $item
     */
    public function addItem(Item $item)
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
     * @param string $route
     * @return Item|null
     */
    public function getItem($route)
    {
        return isset($this->items[$route]) ? $this->items[$route] : null;
    }

    /**
     * @return \ArrayIterator|Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @param callable $callback
     * @throws \InvalidArgumentException
     */
    public function sort(callable $callback)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Given callback is not callable.');
        }
        uasort($this->items, $callback);
    }
}
