<?php

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
