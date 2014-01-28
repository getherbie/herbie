<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Blog;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class PostCollection implements IteratorAggregate, Countable
{

    /**
     * @var array
     */
    protected $items;

    /**
     * @param PostItem $item
     */
    public function addItem(PostItem $item)
    {
        $route = $item->getRoute();
        $this->items[$route] = $item;
    }

    /**
     * @param string $route
     * @return PostItem
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
     * @return string
     */
    public function __toString()
    {
        return 'MenuCollection could not be converted to string.';
    }

}
