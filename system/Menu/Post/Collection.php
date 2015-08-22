<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Post;

use Herbie\Menu\CollectionTrait;
use Herbie\Http\Request;

class Collection implements \IteratorAggregate, \Countable
{

    use CollectionTrait;

    /**
     * @var string
     */
    protected $blogRoute;

    /**
     * @var string
     */
    protected $filteredBy;

    /**
     * @param string $blogRoute
     */
    public function __construct($blogRoute)
    {
        $this->items = [];
        $this->blogRoute = $blogRoute;
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
    public function getFilteredBy()
    {
        return $this->filteredBy;
    }

    /**
     * @return array
     */
    public function getAuthors()
    {
        $authors = [];
        foreach ($this->items as $item) {
            foreach ($item->authors as $author) {
                if (array_key_exists($author, $authors)) {
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
        foreach ($this->items as $item) {
            foreach ($item->categories as $category) {
                if (array_key_exists($category, $categories)) {
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
        foreach ($this->items as $item) {
            if ($i >= $limit) {
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
        foreach ($this->items as $item) {
            foreach ($item->tags as $tag) {
                if (array_key_exists($tag, $tags)) {
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
        foreach ($this->items as $item) {
            $key = substr($item->date, 0, 4);
            if (array_key_exists($key, $years)) {
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
        foreach ($this->items as $item) {
            $year = substr($item->date, 0, 4);
            $month = substr($item->date, 5, 2);
            $key = $year . '-' . $month;
            if (array_key_exists($key, $items)) {
                $count = $items[$key]['count'] + 1;
            } else {
                $count = 1;
            }
            $items[$key] = [
                'year' => $year,
                'month' => $month,
                'date' => $item->date,
                'count' => $count
            ];
        }
        return $items;
    }

    /**
     * @return array
     */
    public function filterItems()
    {
        $pathInfo = $this->getBlogPathInfo();
        if (empty($pathInfo)) {
            // No filtering, return all
            return $this->items;
        }

        $date = null;
        $category = null;
        $tag = null;
        $author = null;

        // filter by year and month
        if (preg_match('/^.*([0-9]{4})\/([0-9]{2})$/', $pathInfo, $matches)) {
            $date = urldecode($matches[1] . '-' . $matches[2]);
            $filteredByLabel = 'Archiv für den Monat';
            // filter by year
        } elseif (preg_match('/^.*([0-9]{4})$/', $pathInfo, $matches)) {
            $date = urldecode($matches[1]);
            $filteredByLabel = 'Archiv für das Jahr';
            // filter by category
        } elseif (preg_match('/^category\/(.+)$/', $pathInfo, $matches)) {
            $category = urldecode($matches[1]);
            // filter by tag
        } elseif (preg_match('/^tag\/(.+)$/', $pathInfo, $matches)) {
            $tag = urldecode($matches[1]);
            // filter by author
        } elseif (preg_match('/^author\/(.+)$/', $pathInfo, $matches)) {
            $author = urldecode($matches[1]);
        } else {
            // Invalid filter setting, return empty array
            return [];
        }

        $items = [];

        foreach ($this->items as $item) {
            if (0 === strpos($item->date, $date . '-')) {
                $this->filteredBy = [
                    'label' => $filteredByLabel,
                    'value' => $date
                ];
                $items[] = $item;
                continue;
            }
            if ($item->hasCategory($category)) {
                $items[] = $item;
                $this->filteredBy = [
                    'label' => 'Archiv für die Kategorie',
                    'value' => $item->getCategory($category)
                ];
                continue;
            }
            if ($item->hasTag($tag)) {
                $items[] = $item;
                $this->filteredBy = [
                    'label' => 'Archiv für das Schlagwort',
                    'value' => $item->getTag($tag)
                ];
                continue;
            }
            if ($item->hasAuthor($author)) {
                $items[] = $item;
                $this->filteredBy = [
                    'label' => 'Archiv für den Author',
                    'value' => $item->getAuthor($author)
                ];
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
        $request = new Request();

        $segments = explode('/', $request->getRoute());
        if (empty($segments)) {
            return '';
        }

        $blogRoute = trim($this->blogRoute, '/');
        if ($segments[0] == $blogRoute) {
            array_shift($segments);
        }

        return implode('/', $segments);
    }
}
