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

namespace Herbie\Page\Iterator;

class RecursiveDirectoryIterator extends \RecursiveDirectoryIterator
{

    /**
     * Return an instance of SplFileInfo with support for relative paths
     *
     * @return SplFileInfo File information
     */
    public function current(): SplFileInfo
    {
        return new SplFileInfo(parent::current()->getPathname(), $this->getSubPath(), $this->getSubPathname());
    }
}
