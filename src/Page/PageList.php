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

namespace Herbie\Page;

class PageList implements \IteratorAggregate, \Countable
{
    /**
     * @var PageItem[]
     */
    private $items = [];

    /**
     * @var bool
     */
    public $fromCache = false;

    /**
     * MenuList constructor.
     * @param PageItem[] $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @param PageItem $item
     */
    public function addItem(PageItem $item): void
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
     * @return PageItem|null
     */
    public function getItem($route): ?PageItem
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
     * @return PageItem
     */
    public function getRandom(): PageItem
    {
        $routes = array_keys($this->items);
        $index = mt_rand(0, $this->count()-1);
        $route = $routes[$index];
        return $this->items[$route];
    }

    /**
     * @param string $value
     * @param string $key
     * @return PageItem|null
     */
    public function find($value, $key): ?PageItem
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
     * @return PageList
     */
    public function filter($key = null, $value = null): PageList
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
     * @return PageList
     */
    public function shuffle(): PageList
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
     * @return PageList
     */
    public function sort($mixed = null, $direction = 'asc'): PageList
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
     * @param string|null $type
     * @return array
     */
    public function getAuthors(?string $type = null): array
    {
        $type = is_null($type) ? '__all__' : $type;
        $authorsPerType = $this->createXxx('authors');
        $authors = $authorsPerType[$type] ?? [];
        ksort($authors);
        return $authors;
    }

    /**
     * @param string|null $type
     * @return array
     */
    public function getCategories(?string $type = null): array
    {
        $type = is_null($type) ? '__all__' : $type;
        $categoriesPerType = $this->createXxx('categories');
        $categories = $categoriesPerType[$type] ?? [];
        ksort($categories);
        return $categories;
    }

    /**
     * @param int $limit
     * @param string|null $type
     * @return array
     */
    public function getRecent(int $limit, ?string $type = null): array
    {
        $limit = intval($limit);
        $items = [];
        $i = 0;
        foreach ($this->items as $pageItem) {
            if ($type && ($pageItem->getType() !== $type)) {
                continue;
            }
            if ($i >= $limit) {
                break;
            }
            $items[] = $pageItem;
            $i++;
        }
        return $items;
    }

    /**
     * @param string|null $type
     * @return array
     */
    public function getTags(?string $type = null): array
    {
        $type = is_null($type) ? '__all__' : $type;
        $tagsPerType = $this->createXxx('tags');
        $tags = $tagsPerType[$type] ?? [];
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
     * @param string|null $type
     * @return array
     */
    public function getMonths(?string $type = null): array
    {
        $type = is_null($type) ? '__all__' : $type;

        // get items
        $items = ['__all__' => []];
        foreach ($this->items as $pageItem) {
            $pageType = $pageItem->getType();

            $year = substr($pageItem->date, 0, 4);
            $month = substr($pageItem->date, 5, 2);
            $key = $year . '-' . $month;

            $item = [
                'year' => $year,
                'month' => $month,
                'date' => $pageItem->date,
                'count' => 1
            ];

            // for all
            if (isset($items['__all__'][$key])) {
                $items['__all__'][$key]['count']++;
            } else {
                $items['__all__'][$key] = $item;
            }

            if (!isset($items[$pageType])) {
                $items[$pageType] = [];
            }
            // per type
            if (isset($items[$pageType][$key])) {
                $items[$pageType][$key]['count']++;
            } else {
                $items[$pageType][$key] = $item;
            }
        }

        $months = $items[$type] ?? [];
        krsort($months);
        return $months;
    }

    /**
     * @param string $type
     * @param string $parentRoute
     * @param array $params
     * @return PageList
     */
    public function filterItems(string $type, string $parentRoute, array $params): PageList
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
            if (isset($params['year'], $params['month'], $params['day'])) {
                $date = sprintf('%s-%s-%s', $params['year'], $params['month'], $params['day']);
                if ($item->getDate() == $date) {
                    $items[] = $item;
                }
                continue;
            }
            if (isset($params['year'], $params['month'])) {
                $date = substr($item->getDate(), 0, 7);
                $month = sprintf('%s-%s', $params['year'], $params['month']);
                if ($date == $month) {
                    $items[] = $item;
                }
                continue;
            }
            if (isset($params['year'])) {
                if (substr($item->getDate(), 0, 4) == $params['year']) {
                    $items[] = $item;
                }
                continue;
            }
        }

        return new static($items);
    }

    /**
     * @param string $dataType
     * @return array
     */
    private function createXxx(string $dataType): array
    {
        $items = ['__all__' => []];
        foreach ($this->items as $pageItem) {
            $pageType = $pageItem->getType();
            foreach ($pageItem->{$dataType} as $item) {
                // for all
                if (isset($items['__all__'][$item])) {
                    $items['__all__'][$item]++;
                } else {
                    $items['__all__'][$item] = 1;
                }

                if (!isset($items[$pageType])) {
                    $items[$pageType] = [];
                }
                // per type
                if (isset($items[$pageType][$item])) {
                    $items[$pageType][$item]++;
                } else {
                    $items[$pageType][$item] = 1;
                }
            }
        }
        return $items;
    }
}
