<?php

namespace Herbie;

class Pagination implements \IteratorAggregate, \Countable
{

    protected $items;
    protected $page;
    protected $limit;

    /**
     * @param array $items
     * @param int $limit
     */
    public function __construct(array $items, $limit = 10)
    {
        $this->items = $items;
        $this->limit = intval($limit);
        $this->page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        $calculated = ceil($this->count() / $this->limit);
        if ($this->page > $calculated) {
            $this->page = $calculated;
        }
        return $this->page;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = intval($limit);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        $offset = ($this->getPage() - 1) * $this->limit;
        $items = array_slice($this->items, $offset, $this->limit);
        return new \ArrayIterator($items);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @return bool
     */
    public function hasNextPage()
    {
        return ($this->limit * $this->getPage()) < $this->count();
    }

    /**
     * @return int
     */
    public function getNextPage()
    {
        return max(2, $this->getPage() + 1);
    }

    /**
     * @return bool
     */
    public function hasPrevPage()
    {
        return 1 < $this->getPage();
    }

    /**
     * @return int
     */
    public function getPrevPage()
    {
        return max(1, $this->getPage() - 1);
    }

}
