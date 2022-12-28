<?php

declare(strict_types=1);

namespace herbie;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, Page>
 */
final class PageList implements IteratorAggregate, Countable
{
    /**
     * @var Page[]
     */
    private array $items;

    private ?PageTrail $pageTrail;

    private ?PageTree $pageTree;

    /**
     * MenuList constructor.
     * @param Page[] $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
        $this->pageTrail = null;
        $this->pageTree = null;
    }

    public function addItem(Page $item): void
    {
        $route = $item->getRoute();
        $this->items[$route] = $item;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function query(): QueryBuilder
    {
        return (new QueryBuilder())->from($this);
    }

    /**
     * Run a filter over each of the items.
     *
     * @param callable|string|null $key
     * @param mixed $value
     */
    public function filter($key = null, $value = null): PageList
    {
        if (is_callable($key)) {
            return new self(array_filter($this->items, $key));
        }
        if (is_string($key) && is_scalar($value)) {
            return new self(
                array_filter($this->items, function ($val) use ($key, $value) {
                    if ($val->{$key} === $value) {
                        return true;
                    }
                    return false;
                })
            );
        }
        return new self(array_filter($this->items));
    }

    /**
     * Shuffle the items in the list.
     */
    public function shuffle(): PageList
    {
        $items = $this->items;
        shuffle($items);
        return new self($items);
    }

    /**
     * @param callable|string|null $mixed
     */
    public function sort($mixed = null, string $direction = 'asc'): PageList
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

    private function createTaxonomyFor(string $dataType): array
    {
        $items = ['__all__' => []];
        foreach ($this->items as $page) {
            $pageType = $page->getType();
            foreach ($page->{$dataType} as $item) {
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

    public function getCategories(?string $type = null): array
    {
        $type = $type === null ? '__all__' : $type;
        $categoriesPerType = $this->createTaxonomyFor('categories');
        $categories = $categoriesPerType[$type] ?? [];
        ksort($categories);
        return $categories;
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
        foreach ($this->items as $page) {
            $pageType = $page->getType();

            $year = substr($page->getDate(), 0, 4);
            $month = substr($page->getDate(), 5, 2);
            $key = $year . '-' . $month;

            $item = [
                'year' => $year,
                'month' => $month,
                'date' => $page->getDate(),
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

    public function getPageTree(): PageTree
    {
        if ($this->pageTree === null) {
            $this->pageTree = (new PageFactory())->newPageTree($this);
        }
        return $this->pageTree;
    }

    public function getPageTrail(string $requestRoute): PageTrail
    {
        // It would be possible to have multiple cached page trails.
        // But in fact, there is always only one for the requested route.
        if ($this->pageTrail) {
            return $this->pageTrail;
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

        return $this->pageTrail = (new PageFactory())->newPageTrail($items);
    }

    public function getItem(string $route): ?Page
    {
        return $this->items[$route] ?? null;
    }
}
