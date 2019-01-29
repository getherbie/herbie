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

namespace Herbie;

class FileInfoSortableIterator implements \IteratorAggregate
{

    const SORT_BY_NAME = 1;
    const SORT_BY_TYPE = 2;
    const SORT_BY_ACCESSED_TIME = 3;
    const SORT_BY_CHANGED_TIME = 4;
    const SORT_BY_MODIFIED_TIME = 5;

    /**
     * @var \Traversable
     */
    private $iterator;

    /**
     * @var int|callable
     */
    private $sort;

    /**
     * @param \Traversable $iterator
     * @param int|callable $sort
     * @throws \InvalidArgumentException
     */
    public function __construct(\Traversable $iterator, $sort)
    {
        $this->iterator = $iterator;

        if (self::SORT_BY_NAME === $sort) {
            $this->sort = function (\SplFileInfo $a, \SplFileInfo $b) {
                return strcmp($a->getRealPath(), $b->getRealPath());
            };
        } elseif (self::SORT_BY_TYPE === $sort) {
            $this->sort = function (\SplFileInfo $a, \SplFileInfo $b) {
                if ($a->isDir() && $b->isFile()) {
                    return -1;
                } elseif ($a->isFile() && $b->isDir()) {
                    return 1;
                }
                return strcmp($a->getRealPath(), $b->getRealPath());
            };
        } elseif (self::SORT_BY_ACCESSED_TIME === $sort) {
            $this->sort = function (\SplFileInfo $a, \SplFileInfo $b) {
                return ($a->getATime() - $b->getATime());
            };
        } elseif (self::SORT_BY_CHANGED_TIME === $sort) {
            $this->sort = function (\SplFileInfo $a, \SplFileInfo $b) {
                return ($a->getCTime() - $b->getCTime());
            };
        } elseif (self::SORT_BY_MODIFIED_TIME === $sort) {
            $this->sort = function (\SplFileInfo $a, \SplFileInfo $b) {
                return ($a->getMTime() - $b->getMTime());
            };
        } elseif (is_callable($sort)) {
            $this->sort = $sort;
        } else {
            $message = 'The SortableIterator takes a PHP callable or a valid built-in sort algorithm as an argument.';
            throw new \InvalidArgumentException($message);
        }
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        $array = iterator_to_array($this->iterator, true);
        uasort($array, $this->sort);

        return new \ArrayIterator($array);
    }
}
