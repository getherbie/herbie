<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <http://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Page;

use Herbie\Menu\CollectionTrait;

class Collection implements \IteratorAggregate, \Countable
{

    use CollectionTrait;

    public $fromCache;

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
