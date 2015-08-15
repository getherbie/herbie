<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Page;

class FileFilterCallback
{

    /**
     * @var array
     */
    private $extensions;

    /**
     * @param array $extensions
     */
    public function __construct($extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     *
     * @param SplFileInfo $file
     * @param string $path
     * @param RecursiveDirectoryIterator $iterator
     * @return boolean
     */
    public function call(\SplFileInfo $file, $path, \RecursiveDirectoryIterator $iterator)
    {
        $firstChar = substr($file->getFileName(), 0, 1);
        if (in_array($firstChar, ['.', '_'])) {
            return false;
        }

        if ($file->isDir()) {
            return true;
        }

        if (!in_array($file->getExtension(), $this->extensions)) {
            return false;
        }

        return true;
    }
}
