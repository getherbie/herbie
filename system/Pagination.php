<?php

declare(strict_types=1);

namespace herbie;

use ArrayIterator;
use Countable;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;

/**
 * @see https://docs.phalcon.io/4.0/en/pagination
 */
final class Pagination implements IteratorAggregate, Countable
{
    /** @var array<int, mixed> */
    private array $items;
    private int $limit;
    private int $page;
    private int $totalItems;

    /**
     * @param array<int, mixed> $items
     * @throws Exception
     * @throws LogicException
     */
    public function __construct(iterable $items, int $limit = 10, int $page = 1)
    {
        $this->items = [];
        if (is_array($items)) {
            $this->items = $items;
        } elseif ($items instanceof IteratorAggregate) {
            $this->items = (array)$items->getIterator();
        } else {
            $message = 'The param $items must be an array or an object implementing IteratorAggregate.';
            throw new InvalidArgumentException($message, 500);
        }
        $this->totalItems = count($items);
        $this->setLimit($limit);
        $this->setCurrentPage($page);
    }

    /**
     * Gets the items on the current page
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getItems());
    }

    /**
     * Gets number of the current page
     */
    public function getCurrentPage(): int
    {
        return $this->page;
    }

    /**
     * Get the number of items on the current page
     */
    public function count(): int
    {
        return count($this->getItems());
    }

    /**
     * Gets the items on the current page
     */
    public function getItems(): array
    {
        return array_slice($this->items, $this->getOffset(), $this->getLimit());
    }

    /**
     * Gets the total number of items
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * Gets current rows limit
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * Gets current rows offset
     */
    public function getOffset(): int
    {
        return ($this->getCurrentPage() - 1) * $this->getLimit();
    }

    private function setLimit(int $limit): void
    {
        $this->limit = min(max($limit, 1), $this->totalItems);
    }

    /**
     * Set the current page number
     */
    public function setCurrentPage(int $page): void
    {
        $this->page = min(max($page, $this->getFirstPage()), $this->getLastPage());
    }

    /**
     * Gets number of the first page
     */
    public function getFirstPage(): int
    {
        return 1;
    }

    /**
     * Gets number of the last page
     */
    public function getLastPage(): int
    {
        $ceil = ceil($this->totalItems / $this->limit);
        return (int)$ceil;
    }

    /**
     * Gets number of the next page
     */
    public function getNextPage(): int
    {
        return min($this->getCurrentPage() + 1, $this->getLastPage());
    }

    /**
     * Gets number of the previous page
     */
    public function getPreviousPage(): int
    {
        return max($this->getCurrentPage() - 1, $this->getFirstPage());
    }
}
