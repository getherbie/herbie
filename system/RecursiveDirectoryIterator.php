<?php

declare(strict_types=1);

namespace herbie;

use RecursiveDirectoryIterator as RecursiveDirectoryIteratorAlias;

final class RecursiveDirectoryIterator extends RecursiveDirectoryIteratorAlias
{
    /**
     * Return an instance of FileInfo with support for relative paths
     */
    public function current(): FileInfo
    {
        return new FileInfo($this->getPathname(), $this->getSubPath(), $this->getSubPathname());
    }
}
