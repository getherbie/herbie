<?php

declare(strict_types=1);

namespace herbie;

use Countable;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, Page>
 */
final class PageTrail implements IteratorAggregate, Countable
{
    /** @var Page[] */
    private array $items;

    /**
     * PageTrail constructor.
     *
     * @param Page[] $items
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
