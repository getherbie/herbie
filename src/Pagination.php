<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

class Pagination implements \IteratorAggregate, \Countable
{
    private array $items;

    private int $limit;

    private string $name;

    /**
     * @throws \Exception
     * @throws \LogicException
     */
    public function __construct(iterable $items, int $limit = 10, string $name = 'page')
    {
        $this->items = [];
        if (is_array($items)) {
            $this->items = $items;
        } elseif ($items instanceof \IteratorAggregate) {
            $this->items = (array)$items->getIterator();
        } else {
            $message = 'The param $items must be an array or an object implementing \IteratorAggregate.';
            throw new \LogicException($message, 500);
        }
        $this->setLimit($limit);
        $this->name = $name;
    }

    public function getPage(): int
    {
        $page = isset($_GET[$this->name]) ? intval($_GET[$this->name]) : 1;
        $calculated = ceil($this->count() / $this->limit);
        if ($page > $calculated) {
            $page = $calculated;
        }
        return intval($page);
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $limit = (0 == $limit) ? 1000 : intval($limit);
        $this->limit = $limit;
    }

    public function getIterator(): \ArrayIterator
    {
        $offset = ($this->getPage() - 1) * $this->limit;
        $items = array_slice($this->items, $offset, $this->limit);
        return new \ArrayIterator($items);
    }

    public function count(): int
    {
        return count($this->items);
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
