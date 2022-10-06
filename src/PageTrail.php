<?php

declare(strict_types=1);

namespace herbie;

final class PageTrail implements \IteratorAggregate, \Countable
{
    private array $items;

    /**
     * PageTrail constructor.
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
