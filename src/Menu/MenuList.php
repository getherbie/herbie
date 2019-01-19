<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie\Menu;

class MenuList implements \IteratorAggregate, \Countable
{
    /**
     * @var MenuItem[]
     */
    private $items = [];

    /**
     * @var array
     */
    private $filteredBy = [];

    /**
     * @var bool
     */
    public $fromCache = false;

    /**
     * MenuList constructor.
     * @param MenuItem[] $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @param MenuItem $item
     */
    public function addItem(MenuItem $item): void
    {
        $route = $item->getRoute();
        $this->items[$route] = $item;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param string $route
     * @return MenuItem|null
     */
    public function getItem($route): ?MenuItem
    {
        return isset($this->items[$route]) ? $this->items[$route] : null;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return MenuItem
     */
    public function getRandom(): MenuItem
    {
        $routes = array_keys($this->items);
        $index = mt_rand(0, $this->count()-1);
        $route = $routes[$index];
        return $this->items[$route];
    }

    /**
     * @param string $value
     * @param string $key
     * @return MenuItem|null
     */
    public function find($value, $key): ?MenuItem
    {
        foreach ($this->items as $item) {
            if ($item->$key == $value) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Run a filter over each of the items.
     *
     * @param callable|null $key
     * @param mixed $value
     * @return MenuList
     */
    public function filter($key = null, $value = null): MenuList
    {
        if (is_callable($key)) {
            return new static(array_filter($this->items, $key));
        }
        if (is_string($key) && is_scalar($value)) {
            return new static(array_filter($this->items, function ($val) use ($key, $value) {
                if ($val->{$key} == $value) {
                    return true;
                }
                return false;
            }));
        }
        return new static(array_filter($this->items));
    }

    /**
     * Shuffle the items in the list.
     *
     * @return MenuList
     */
    public function shuffle(): MenuList
    {
        $items = $this->items;
        shuffle($items);
        return new static($items);
    }

    /**
     * @return array
     */
    public function flatten(): array
    {
        return $this->items;
    }

    /**
     * @param callable|string|null $mixed
     * @param string $direction
     * @return MenuList
     */
    public function sort($mixed = null, $direction = 'asc'): MenuList
    {
        $items = $this->items;

        if (is_callable($mixed)) {
            uasort($items, $mixed);
            return new static($items);
        }

        $field = is_string($mixed) ? $mixed : 'title';
        uasort($items, function ($a, $b) use ($field, $direction) {
            if ($a->{$field} == $b->{$field}) {
                return 0;
            }
            if ($direction == 'asc') {
                return ($a->{$field} < $b->{$field}) ? -1 : 1;
            } else {
                return ($b->{$field} < $a->{$field}) ? -1 : 1;
            }
        });

        return new static($items);
    }

    /**
     * @return array
     */
    public function getFilteredBy(): array
    {
        return $this->filteredBy;
    }

    /**
     * @return array
     */
    public function getAuthors(): array
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
    public function getCategories(): array
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
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit): array
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
    public function getTags(): array
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
    public function getYears(): array
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
    public function getMonths(): array
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
     * @param string $type
     * @param string $parentRoute
     * @param array $params
     * @return MenuList
     */
    public function filterItems(string $type, string $parentRoute, array $params): MenuList
    {
        $items = [];

        foreach ($this->items as $item) {
            if ($item->getType() !== $type) {
                continue;
            }
            if ($item->getParentRoute() !== $parentRoute) {
                continue;
            }
            if (empty($params)) {
                $items[] = $item;
                continue;
            }
            if (isset($params['category']) && $item->hasCategory($params['category'])) {
                $items[] = $item;
                continue;
            }
            if (isset($params['tag']) && $item->hasTag($params['tag'])) {
                $items[] = $item;
                continue;
            }
            if (isset($params['author']) && $item->hasAuthor($params['author'])) {
                $items[] = $item;
                continue;
            }
        }

        return new static($items);
    }
}
