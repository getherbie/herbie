<?php

declare(strict_types=1);

namespace herbie;

use Countable;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, MenuItem>
 */
final class MenuTrail implements IteratorAggregate, Countable
{
    /** @var MenuItem[] */
    private array $items;

    /**
     * @param MenuItem[] $items
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
