<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * @see http://www.ruempler.eu/2008/08/09/php-sortingiterator
 */
class SortingIterator implements IteratorAggregate
{
    /**
     * @var Traversable
     */
    protected $iterator;

    /**
     * @param Traversable $iterator
     * @param callable $callback
     * @throws InvalidArgumentException
     */
    public function __construct(Traversable $iterator, $callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Given callback is not callable!');
        }

        $array = iterator_to_array($iterator);
        usort($array, $callback);
        $this->iterator = new ArrayIterator($array);
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return $this->iterator;
    }
}
