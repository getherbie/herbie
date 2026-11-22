<?php

declare(strict_types=1);

namespace herbie;

use ArrayIterator;
use Countable;
use Exception;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;

/**
 * @see https://docs.phalcon.io/4.0/en/pagination
 */
/** @implements \IteratorAggregate<int, mixed> */
final class Pagination implements IteratorAggregate, Countable
{
    /** @var array<int, mixed> */
    private array $items;
    private int $limit;
    private int $page;
    private readonly int $totalItems;

    /**
     * @throws Exception
     */
    public function __construct(iterable $items, int $limit = 10, ?int $page = null)
    {
        if (is_array($items)) {
            $this->items = $items;
        } elseif ($items instanceof IteratorAggregate) {
            $this->items = iterator_to_array($items->getIterator());
        } elseif ($items instanceof Iterator) {
            $this->items = iterator_to_array($items);
        } else {
            $message = 'The param $items must be an array or a traversable object.';
            throw new InvalidArgumentException($message, 500);
        }
        $this->totalItems = count($this->items);
        $this->setLimit($limit);
        $this->setCurrentPage($page ?? $this->getPageFromRequest());
    }

    private function getPageFromRequest(): int
    {
        $raw = $_GET['page'] ?? 1;
        if (is_array($raw)) {
            return 1;
        }
        $page = filter_var($raw, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        return is_int($page) ? $page : 1;
    }

    /**
     * Gets the items on the current page
     */
    /** @return \ArrayIterator<int, mixed> */
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
        if ($this->totalItems === 0) {
            $this->limit = max($limit, 1);
            return;
        }
        $limit = $limit < 1 ? $this->totalItems : $limit;
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
        if ($this->totalItems === 0) {
            return $this->getFirstPage();
        }
        return (int)ceil($this->totalItems / $this->limit);
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
