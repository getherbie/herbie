<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Blog;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Symfony\Component\HttpFoundation\Request;

class PostCollection implements IteratorAggregate, Countable
{

    /**
     * @var array
     */
    protected $items;

    /**
     * @var string
     */
    protected $blogRoute;

    /**
     * @param string $blogRoute
     */
    public function __construct($blogRoute)
    {
        $this->items = array();
        $this->blogRoute = $blogRoute;
    }

    /**
     * @param PostItem $item
     */
    public function addItem(PostItem $item)
    {
        $route = $item->getRoute();
        $this->items[$route] = $item;
    }

    /**
     * @return string
     */
    public function getBlogRoute()
    {
        return $this->blogRoute;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param string $route
     * @return PostItem
     */
    public function getItem($route)
    {
        return isset($this->items[$route]) ? $this->items[$route] : NULL;
    }

    /**
     * @return array
     */
    public function getYears()
    {
        $years = [];
        foreach($this->items AS $item) {
            $years[] = substr($item->date, 0, 4);
        }
        return array_unique($years);
    }

    /**
     * @return array
     */
    public function getMonths()
    {
        $items = [];
        foreach($this->items AS $item) {
            $year = substr($item->date, 0, 4);
            $month = substr($item->date, 5, 2);
            $key = $year . '-' . $month;
            $items[$key] = array(
                'year' => $year,
                'month' => $month,
                'date' => $item->date
            );
        }
        return $items;
    }

    /**
     * @return ArrayIterator|Traversable
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'MenuCollection could not be converted to string.';
    }

    /**
     * @return array
     */
    public function filterItems()
    {
        $request = Request::createFromGlobals();
        $pathInfo = rtrim($request->getPathInfo(), '/');

        if(empty($pathInfo)) {
            // No filtering, return all
            return $this->items;
        }

        $date = null;

        // filter by year and month
        if(preg_match('/^.*([0-9]{4})\/([0-9]{2})$/', $pathInfo, $matches)) {
            $date = $matches[1] . '-' . $matches[2];
        // filter by year
        } elseif(preg_match('/^.*([0-9]{4})$/', $pathInfo, $matches)) {
            $date = $matches[1];
        } else {

            // Invalid filter setting, return empty array
            return [];
        }

        $items = array();

        foreach($this->items AS $item) {
            if(0 === strpos($item->date, $date.'-')) {
                $items[] = $item;
                continue;
            }
        }

        // Return filtered items
        return $items;
    }

}
