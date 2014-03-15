<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;

class MenuCollection implements IteratorAggregate, Countable
{

    /**
     * @var array
     */
    protected $items;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->items = array();
    }

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
     * @param string $route
     * @return MenuItem|null
     */
    public function getItem($route)
    {
        return isset($this->items[$route]) ? $this->items[$route] : NULL;
    }

    /**
     * @return ArrayIterator|Traversable
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
     * @param callable $callback
     * @throws InvalidArgumentException
     */
    public function sort(callable $callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Given callback is not callable.');
        }
        uasort($this->items, $callback);
    }

}
