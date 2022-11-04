<?php

declare(strict_types=1);

namespace herbie;

use EmptyIterator;
use FilesystemIterator;
use IteratorAggregate;
use RecursiveCallbackFilterIterator;
use RecursiveIteratorIterator;
use Traversable;

final class FlatFileIterator implements IteratorAggregate
{
    private Traversable $iterator;
    private string $path;
    private array $extensions;

    public function __construct(string $path, array $extensions)
    {
        $this->path = $path;
        $this->extensions = $extensions;
    }

    public function getIterator(): Traversable
    {
        if (!is_dir($this->path) || empty($this->extensions)) {
            return new EmptyIterator();
        }

        $recDirectoryIt = new RecursiveDirectoryIterator($this->path, FilesystemIterator::SKIP_DOTS);
        $callback = new FileInfoFilterCallback($this->extensions);
        $recCallbackFilterIt = new RecursiveCallbackFilterIterator($recDirectoryIt, $callback);

        $recIteratorIt = new RecursiveIteratorIterator($recCallbackFilterIt);
        return new FileInfoSortableIterator($recIteratorIt, FileInfoSortableIterator::SORT_BY_NAME);
    }
}
