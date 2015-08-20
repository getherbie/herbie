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

class SplFileInfo extends \SplFileInfo
{
    private $relativePath;
    private $relativePathname;

    /**
     * Constructor
     *
     * @param string $file
     * @param string $relativePath
     * @param string $relativePathname
     */
    public function __construct($file, $relativePath, $relativePathname)
    {
        parent::__construct($file);
        $this->relativePath = $relativePath;
        $this->relativePathname = $relativePathname;
    }

    /**
     * Returns the relative path
     *
     * @return string
     */
    public function getRelativePath()
    {
        return $this->relativePath;
    }

    /**
     * Returns the relative path name
     *
     * @return string
     */
    public function getRelativePathname()
    {
        return $this->relativePathname;
    }

    /**
     * @return bool
     */
    public function isDot()
    {
        return in_array($this->getBasename(), ['.', '..']);
    }

    /**
     * Returns the contents of the file
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getContents()
    {
        $level = error_reporting(0);
        $content = file_get_contents($this->getPathname());
        error_reporting($level);
        if (false === $content) {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
        }

        return $content;
    }
}
