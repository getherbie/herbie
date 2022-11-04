<?php

declare(strict_types=1);

namespace herbie;

final class FileInfo extends \SplFileInfo
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
        return file_read($this->getPathname());
    }
}
