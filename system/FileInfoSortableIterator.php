<?php

declare(strict_types=1);

namespace herbie;

use ArrayIterator;
use Closure;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

final class FileInfoSortableIterator implements IteratorAggregate
{
    public const SORT_BY_NONE = 0;
    public const SORT_BY_NAME = 1;
    public const SORT_BY_TYPE = 2;
    public const SORT_BY_ACCESSED_TIME = 3;
    public const SORT_BY_CHANGED_TIME = 4;
    public const SORT_BY_MODIFIED_TIME = 5;

    private Traversable $iterator;
    private Closure|int $sort;

    public function __construct(Traversable $iterator, int|callable $sort)
    {
        $this->iterator = $iterator;

        if (self::SORT_BY_NAME === $sort) {
            $this->sort = function (FileInfo $a, FileInfo $b) {
                return strcmp($a->getRealPath(), $b->getRealPath());
            };
        } elseif (self::SORT_BY_TYPE === $sort) {
            $this->sort = function (FileInfo $a, FileInfo $b) {
                if ($a->isDir() && $b->isFile()) {
                    return -1;
                } elseif ($a->isFile() && $b->isDir()) {
                    return 1;
                }
                return strcmp($a->getRealPath(), $b->getRealPath());
            };
        } elseif (self::SORT_BY_ACCESSED_TIME === $sort) {
            $this->sort = function (FileInfo $a, FileInfo $b) {
                return ($a->getATime() - $b->getATime());
            };
        } elseif (self::SORT_BY_CHANGED_TIME === $sort) {
            $this->sort = function (FileInfo $a, FileInfo $b) {
                return ($a->getCTime() - $b->getCTime());
            };
        } elseif (self::SORT_BY_MODIFIED_TIME === $sort) {
            $this->sort = function (FileInfo $a, FileInfo $b) {
                return ($a->getMTime() - $b->getMTime());
            };
        } elseif (self::SORT_BY_NONE === $sort) {
            $this->sort = $sort;
        } elseif (is_callable($sort)) {
            $this->sort = $sort;
        } else {
            $message = 'The SortableIterator takes a PHP callable or a valid built-in sort algorithm as an argument.';
            throw new InvalidArgumentException($message);
        }
    }

    public function getIterator(): ArrayIterator
    {
        if (self::SORT_BY_NONE === $this->sort) {
            return $this->iterator;
        }

        $array = iterator_to_array($this->iterator, true);
        uasort($array, $this->sort);

        return new ArrayIterator($array);
    }
}
