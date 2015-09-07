<?php

namespace Herbie;

use Herbie\Menu\Page\Collection;

class Pagination implements \IteratorAggregate, \Countable
{

    protected $items;
    protected $limit;
    protected $name;

    /**
     * @param Collection|array $items
     * @param int $limit
     * @param string $name
     */
    public function __construct($items, $limit = 10, $name = 'page')
    {
        $this->items = [];
        if (is_array($items)) {
            $this->items = $items;
        }
        if ($items instanceof Collection) {
            $this->items = $items->flatten();
        }
        $this->limit = $this->setLimit($limit);
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        $page = isset($_GET[$this->name]) ? intval($_GET[$this->name]) : 1;
        $calculated = ceil($this->count() / $this->limit);
        if ($page > $calculated) {
            $page = $calculated;
        }
        return $page;
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
        $limit = (0 == $limit) ? 1000 : intval($limit);
        $this->limit = $limit;
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
