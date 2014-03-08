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
    public function getAuthors()
    {
        $authors = [];
        foreach($this->items AS $item) {
            foreach($item->authors AS $author) {
                if(array_key_exists($author, $authors)) {
                    $count = $authors[$author] + 1;
                } else {
                    $count = 1;
                }
                $authors[$author] = $count;
            }
        }
        ksort($authors);
        return $authors;
    }

    /**
     * @return array
     */
    public function getCategories()
    {
        $categories = [];
        foreach($this->items AS $item) {
            foreach($item->categories AS $category) {
                if(array_key_exists($category, $categories)) {
                    $count = $categories[$category] + 1;
                } else {
                    $count = 1;
                }
                $categories[$category] = $count;
            }
        }
        ksort($categories);
        return $categories;
    }

    /**
     * @param integer $limit
     * @return array
     */
    public function getRecent($limit)
    {
        $limit = intval($limit);
        $items = [];
        $i = 0;
        foreach($this->items AS $item) {
            if($i>=$limit) {
                break;
            }
            $items[] = $item;
            $i++;
        }
        return $items;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        $tags = [];
        foreach($this->items AS $item) {
            foreach($item->tags AS $tag) {
                if(array_key_exists($tag, $tags)) {
                    $count = $tags[$tag] + 1;
                } else {
                    $count = 1;
                }
                $tags[$tag] = $count;
            }
        }
        ksort($tags);
        return $tags;
    }

    /**
     * @return array
     */
    public function getYears()
    {
        $years = [];
        foreach($this->items AS $item) {
            $key = substr($item->date, 0, 4);
            if(array_key_exists($key, $years)) {
                $count = $years[$key] + 1;
            } else {
                $count = 1;
            }
            $years[$key] = $count;
        }
        return $years;
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
            if(array_key_exists($key, $items)) {
                $count = $items[$key]['count'] + 1;
            } else {
                $count = 1;
            }
            $items[$key] = array(
                'year' => $year,
                'month' => $month,
                'date' => $item->date,
                'count' => $count
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
        $pathInfo = $this->getBlogPathInfo();
        if(empty($pathInfo)) {
            // No filtering, return all
            return $this->items;
        }

        $date = null;
        $category = null;
        $tag = null;
        $author = null;

        // filter by year and month
        if(preg_match('/^.*([0-9]{4})\/([0-9]{2})$/', $pathInfo, $matches)) {
            $date = urldecode($matches[1] . '-' . $matches[2]);
        // filter by year
        } elseif(preg_match('/^.*([0-9]{4})$/', $pathInfo, $matches)) {
            $date = urldecode($matches[1]);
        // filter by category
        } elseif(preg_match('/^category\/([A-Za-z0-9]+)$/', $pathInfo, $matches)) {
            $category = urldecode($matches[1]);
        // filter by tag
        } elseif(preg_match('/^tag\/([A-Za-z0-9]+)$/', $pathInfo, $matches)) {
            $tag = urldecode($matches[1]);
        // filter by author
        } elseif(preg_match('/^author\/([A-Za-z0-9%]+)$/', $pathInfo, $matches)) {
            $author = urldecode($matches[1]);
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
            if($item->hasCategory($category)) {
                $items[] = $item;
                continue;
            }
            if($item->hasTag($tag)) {
                $items[] = $item;
                continue;
            }
            if($item->hasAuthor($author)) {
                $items[] = $item;
                continue;
            }
        }

        // Return filtered items
        return $items;
    }

    /**
     * @return string
     */
    protected function getBlogPathInfo()
    {
        $request = Request::createFromGlobals();
        $pathInfo = trim($request->getPathInfo(), '/');

        $segments = explode('/', $pathInfo);
        if(empty($segments)) {
            return '';
        }

        $blogRoute = trim($this->blogRoute, '/');
        if($segments[0] == $blogRoute) {
            array_shift($segments);
        }

        return implode('/', $segments);
    }

}
