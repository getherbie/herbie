<?php

namespace herbie;

use Countable;
use Exception;
use herbie\finder\CustomFilterIterator;
use herbie\finder\DepthRangeFilterIterator;
use herbie\finder\FileTypeFilterIterator;
use IteratorAggregate;
use RecursiveIteratorIterator;

final class Finder implements IteratorAggregate, Countable
{
    private int $mode = 0;
    private array $dirs = [];

    private function __construct()
    {
    }

    public function directories(): static
    {
        $this->mode = FileTypeFilterIterator::ONLY_DIRECTORIES;

        return $this;
    }

    public function files(): static
    {
        $this->mode = FileTypeFilterIterator::ONLY_FILES;

        return $this;
    }

    public function in(string|array $dirs): static
    {
        $resolvedDirs = [];

        foreach ((array) $dirs as $dir) {
            if (is_dir($dir)) {
                $resolvedDirs[] = [$this->normalizeDir($dir)];
            } else {
                throw new Exception(sprintf('The "%s" directory does not exist.', $dir));
            }
        }

        $this->dirs = array_merge($this->dirs, ...$resolvedDirs);

        return $this;
    }

    public function getIterator(): \Iterator
    {
        
    }

    private function searchIterators()
    {
        // create iterator
        $dir = dirname(__DIR__) . '/site/pages';
        $flags = RecursiveDirectoryIterator::SKIP_DOTS;

        $iterator = new RecursiveDirectoryIterator($dir, $flags);
        $iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);

// filtering
        $iterator = new DepthRangeFilterIterator($iterator, 0, 3);
        $iterator = new FileTypeFilterIterator($iterator, 0);
        $iterator = new CustomFilterIterator($iterator, [function (FileInfo $file) {
            if (strlen($file->getFilename()) > 12) {
                return false;
            }
        }]);
    }
    
    public function count(): int
    {
        return iterator_count($this->getIterator());
    }
}
