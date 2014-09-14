<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

use ArrayIterator;
use Countable;
use IteratorAggregate;

class Paginator implements IteratorAggregate, Countable
{
    /**
     * @var array
     */
    protected $items;

    protected $request;

    protected $date;

    protected $author;

    protected $category;

    /**
     * Constructor
     */
    public function __construct($collection, $request)
    {
        $this->items = $collection->filterItems();
        $this->request = $request;
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
