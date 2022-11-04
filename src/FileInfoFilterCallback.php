<?php

declare(strict_types=1);

namespace herbie;

final class FileInfoFilterCallback
{
    /** @var string[] */
    private array $extensions;

    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }

    public function __invoke(FileInfo $file, string $path, \RecursiveDirectoryIterator $iterator): bool
    {
        $firstChar = substr($file->getFilename(), 0, 1);
        if (in_array($firstChar, ['.', '_'])) {
            return false;
        }

        // Allow recursion
        if ($iterator->hasChildren()) {
            return true;
        }

        if (in_array($file->getExtension(), $this->extensions)) {
            return true;
        }

        return false;
    }
}
