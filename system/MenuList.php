<?php

declare(strict_types=1);

namespace herbie;

use Countable;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, MenuItem>
 */
final class MenuList implements IteratorAggregate, Countable
{
    /**
     * @var MenuItem[]
     */
    private array $items;

    private ?MenuTrail $menuTrail;

    private ?MenuTree $menuTree;

    /**
     * MenuList constructor.
     * @param MenuItem[] $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
        $this->menuTrail = null;
        $this->menuTree = null;
    }

    public function addItem(MenuItem $item): void
    {
        $route = $item->getRoute();
        $this->items[$route] = $item;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getItem(string $route): ?MenuItem
    {
        return $this->items[$route] ?? null;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getRandom(): MenuItem
    {
        $routes = array_keys($this->items);
        $index = mt_rand(0, $this->count() - 1);
        $route = $routes[$index];
        return $this->items[$route];
    }

    public function find(string $value, string $key): ?MenuItem
    {
        foreach ($this->items as $item) {
            if ($item->$key === $value) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Run a filter over each of the items.
     *
     * @param callable|string|null $key
     * @param mixed $value
     */
    public function filter($key = null, $value = null): MenuList
    {
        if (is_callable($key)) {
            return new self(array_filter($this->items, $key));
        }
        if (is_string($key) && is_scalar($value)) {
            return new self(array_filter($this->items, function ($val) use ($key, $value) {
                if ($val->{$key} === $value) {
                    return true;
                }
                return false;
            }));
        }
        return new self(array_filter($this->items));
    }

    /**
     * Shuffle the items in the list.
     */
    public function shuffle(): MenuList
    {
        $items = $this->items;
        shuffle($items);
        return new self($items);
    }

    public function flatten(): array
    {
        return $this->items;
    }

    /**
     * @param callable|string|null $mixed
     */
    public function sort($mixed = null, string $direction = 'asc'): MenuList
    {
        $items = $this->items;

        if (is_callable($mixed)) {
            uasort($items, $mixed);
            return new self($items);
        }

        $field = is_string($mixed) ? $mixed : 'title';
        uasort($items, function ($a, $b) use ($field, $direction) {
            if ($a->{$field} === $b->{$field}) {
                return 0;
            }
            if ($direction === 'asc') {
                return ($a->{$field} < $b->{$field}) ? -1 : 1;
            } else {
                return ($b->{$field} < $a->{$field}) ? -1 : 1;
            }
        });

        return new self($items);
    }

    public function getAuthors(?string $type = null): array
    {
        $type = $type === null ? '__all__' : $type;
        $authorsPerType = $this->createTaxonomyFor('authors');
        $authors = $authorsPerType[$type] ?? [];
        ksort($authors);
        return $authors;
    }

    public function getCategories(?string $type = null): array
    {
        $type = $type === null ? '__all__' : $type;
        $categoriesPerType = $this->createTaxonomyFor('categories');
        $categories = $categoriesPerType[$type] ?? [];
        ksort($categories);
        return $categories;
    }

    public function getRecent(int $limit, ?string $type = null): array
    {
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

    public function getTags(?string $type = null): array
    {
        $type = $type === null ? '__all__' : $type;
        $tagsPerType = $this->createTaxonomyFor('tags');
        $tags = $tagsPerType[$type] ?? [];
        ksort($tags);
        return $tags;
    }

    public function getYears(): array
    {
        $years = [];
        foreach ($this->items as $item) {
            $key = substr($item->getDate(), 0, 4);
            if (array_key_exists($key, $years)) {
                $count = $years[$key] + 1;
            } else {
                $count = 1;
            }
            $years[$key] = $count;
        }
        return $years;
    }

    public function getMonths(?string $type = null): array
    {
        $type = $type === null ? '__all__' : $type;

        // get items
        $items = ['__all__' => []];
        foreach ($this->items as $pageItem) {
            $pageType = $pageItem->getType();

            $year = substr($pageItem->getDate(), 0, 4);
            $month = substr($pageItem->getDate(), 5, 2);
            $key = $year . '-' . $month;

            $item = [
                'year' => $year,
                'month' => $month,
                'date' => $pageItem->getDate(),
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
            if (isset($params['year'], $params['month'], $params['day'])) {
                $date = sprintf('%s-%s-%s', $params['year'], $params['month'], $params['day']);
                if ($item->getDate() === $date) {
                    $items[] = $item;
                }
                continue;
            }
            if (isset($params['year'], $params['month'])) {
                $date = substr($item->getDate(), 0, 7);
                $month = sprintf('%s-%s', $params['year'], $params['month']);
                if ($date === $month) {
                    $items[] = $item;
                }
                continue;
            }
            if (isset($params['year'])) {
                if (substr($item->getDate(), 0, 4) === $params['year']) {
                    $items[] = $item;
                }
                continue;
            }
        }

        return new self($items);
    }

    private function createTaxonomyFor(string $dataType): array
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

    public function getMenuTree(): MenuTree
    {
        if ($this->menuTree === null) {
            $this->menuTree = (new PageFactory())->newMenuTree($this);
        }
        return $this->menuTree;
    }

    public function getMenuTrail(string $requestRoute): MenuTrail
    {
        // It would be possible to have multiple cached page trails.
        // But in fact, there is always only one for the requested route.
        if ($this->menuTrail) {
            return $this->menuTrail;
        }

        $items = [];

        $segments = explode('/', rtrim($requestRoute, '/'));
        $route = '';
        $delim = '';
        foreach ($segments as $segment) {
            $route .= $delim . $segment;
            $delim = '/';

            $item = $this->getItem($route);
            if (isset($item)) {
                $items[] = $item;
            }
        }

        return $this->menuTrail = (new PageFactory())->newMenuTrail($items);
    }
}
