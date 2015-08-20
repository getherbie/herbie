<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Finder;

class Finder implements \IteratorAggregate, \Countable
{

    private $mode = 0;
    private $dir = null;
    private $hidden = false;
    private $extensions = [];
    private $minDepth = -1;
    private $maxDepth = PHP_INT_MAX;

    /**
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * @return $this
     */
    public function directories()
    {
        $this->mode = FileTypeFilterIterator::ONLY_DIRECTORIES;
        return $this;
    }

    /**
     * @return $this
     */
    public function files()
    {
        $this->mode = FileTypeFilterIterator::ONLY_FILES;
        return $this;
    }

    /**
     * @param array|string $extensions
     * @return $this
     */
    public function extensions($extensions)
    {
        if (is_string($extensions)) {
            $extensions = empty($extensions) ? [] : explode(',', $extensions);
        }
        $this->extensions = $extensions;
        return $this;
    }

    /**
     * @param bool $hidden
     * @return $this
     */
    public function hidden($hidden = true)
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * @param string $dir
     * @return $this
     */
    public function in($dir)
    {
        $this->dir = $dir;
        return $this;
    }

    public function range($minDepth, $maxDepth = PHP_INT_MAX)
    {
        $this->minDepth = $minDepth;
        $this->maxDepth = $maxDepth;
        return $this;
    }

    /**
     * @return \Traversable
     */
    public function getIterator()
    {
        if (empty($this->dir)) {
            throw new \LogicException('You must call in() method before iterating over a Finder.');
        }

        if (is_null($this->mode)) {
            throw new \LogicException('You must call files() or directories() method before iterating over a Finder.');
        }

        $iterator = new \RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        if (!empty($this->hidden)) {
            $iterator = new HiddenFileFilterIterator($iterator);
        }

        if (!empty($this->mode)) {
            $iterator = new FileTypeFilterIterator($iterator, $this->mode);
        }

        if (!empty($this->extensions)) {
            $iterator = new ExtensionFilterIterator($iterator, $this->extensions);
        }

        if ($this->minDepth > -1 || $this->maxDepth < PHP_INT_MAX) {
            #$iterator = new DepthRangeFilterIterator($iterator, $this->minDepth, $this->maxDepth);
        }

        return $iterator;
    }

    /**
     * @return int
     */
    public function count()
    {
        return iterator_count($this->getIterator());
    }
}
