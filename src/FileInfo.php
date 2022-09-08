<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace herbie;

class FileInfo extends \SplFileInfo
{
    private string $relativePath;

    private string $relativePathname;

    public function __construct(string $file, string $relativePath, string $relativePathname)
    {
        parent::__construct($file);
        $this->relativePath = $relativePath;
        $this->relativePathname = $relativePathname;
    }

    /**
     * Returns the relative path
     */
    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    /**
     * Returns the relative path name
     */
    public function getRelativePathname(): string
    {
        return $this->relativePathname;
    }

    public function isDot(): bool
    {
        return in_array($this->getBasename(), ['.', '..']);
    }

    /**
     * Returns the contents of the file
     */
    public function getContents(): string
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
