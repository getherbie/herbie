<?php

declare(strict_types=1);

namespace herbie;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, Page>
 */
final readonly class PageTrail implements IteratorAggregate, Countable
{
    /**
     * PageTrail constructor.
     *
     * @param Page[] $items
     */
    public function __construct(private array $items) {}

    /** @return \ArrayIterator<int, Page> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
