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

class RecursiveDirectoryIterator extends \RecursiveDirectoryIterator
{

    /**
     * Return an instance of SplFileInfo with support for relative paths
     *
     * @return SplFileInfo File information
     */
    public function current()
    {
        return new SplFileInfo(parent::current()->getPathname(), $this->getSubPath(), $this->getSubPathname());
    }
}
