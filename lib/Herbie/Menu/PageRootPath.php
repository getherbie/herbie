<?php

/**
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
use IteratorAggregate;

class PageRootPath implements IteratorAggregate, Countable
{
    /**
     * @var PageMenuCollection
     */
    protected $collection;

    /**
     * @var string
     */
    protected $route;

    /**
     * @var array
     */
    protected $items;

    /**
     * @param PageMenuCollection $collection
     * @param string $route
     */
    public function __construct(PageMenuCollection $collection, $route)
    {
        $this->collection = $collection;
        $this->route = $route;
        $this->items = $this->buildRootPath();
    }

    /**
     * @return array
     */
    protected function buildRootPath()
    {
        $items = [];

        $segments = explode('/', rtrim($this->route, '/'));
        $route = '';
        $delim = '';
        foreach ($segments as $segment) {
            $route .= $delim . $segment;
            $delim = '/';

            $item = $this->collection->getItem($route);
            if (isset($item)) {
                $items[] = $item;
            }
        }

        return $items;
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
}
