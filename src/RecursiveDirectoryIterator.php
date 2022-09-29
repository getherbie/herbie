<?php

/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

final class RecursiveDirectoryIterator extends \RecursiveDirectoryIterator
{
    /**
     * Return an instance of SplFileInfo with support for relative paths
     */
    public function current(): FileInfo
    {
        return new FileInfo(parent::current()->getPathname(), $this->getSubPath(), $this->getSubPathname());
    }
}
