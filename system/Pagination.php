<?php

declare(strict_types=1);

namespace herbie;

use ArrayIterator;
use Countable;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;

final class Pagination implements IteratorAggregate, Countable
{
    /** @var array<int, mixed> */
    private array $items;

    private int $limit;

    private string $name;

    /**
     * @param array<int, mixed> $items
     * @throws Exception
     * @throws LogicException
     */
    public function __construct(iterable $items, int $limit = 10, string $name = 'page')
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
        $this->setLimit($limit);
        $this->name = $name;
    }

    public function getIterator(): ArrayIterator
    {
        $offset = ($this->getPage() - 1) * $this->limit;
        $items = array_slice($this->items, $offset, $this->limit);
        return new ArrayIterator($items);
    }

    public function getPage(): int
    {
        $page = isset($_GET[$this->name]) ? (int)$_GET[$this->name] : 1;
        $calculated = ceil($this->count() / $this->limit);
        if ($page > $calculated) {
            $page = $calculated;
        }
        return (int)$page;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $limit = (0 === $limit) ? 1000 : $limit;
        $this->limit = $limit;
    }

    public function hasNextPage(): bool
    {
        return ($this->limit * $this->getPage()) < $this->count();
    }

    public function getNextPage(): int
    {
        return max(2, $this->getPage() + 1);
    }

    public function hasPrevPage(): bool
    {
        return 1 < $this->getPage();
    }

    public function getPrevPage(): int
    {
        return max(1, $this->getPage() - 1);
    }
}
