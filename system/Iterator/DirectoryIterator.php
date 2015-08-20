<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Iterator;

class DirectoryIterator extends \DirectoryIterator
{

    protected $root;

    public function __construct($path, $root)
    {
        $this->root = $root;
        parent::__construct($path);
    }

    /**
     * Return an instance of SplFileInfo with support for relative paths
     *
     * @return SplFileInfo File information
     */
    public function current()
    {
        $relativePath = str_replace($this->root.'/', '', parent::current()->getPath());
        $relativePathname = str_replace($this->root.'/', '', parent::current()->getPathname());
        return new SplFileInfo(parent::current()->getPathname(), $relativePath, $relativePathname);
    }
}
