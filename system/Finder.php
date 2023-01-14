<?php

declare(strict_types=1);

namespace herbie;

final class Finder
{
    private string $path;

    /** @var array|string[]  */
    private array $extensions;

    public function __construct(string $path, array $extensions)
    {
        $this->path = $path;
        $this->extensions = $extensions;
    }

    public function pageFiles(): \Symfony\Component\Finder\Finder
    {
        $patterns = $this->getPatterns($this->extensions);
        return (new \Symfony\Component\Finder\Finder())
            ->files()
            ->in($this->path)
            ->notPath('#(^|/)_.+(/|$)#') // ignore underscore files and folders
            ->name($patterns)
            ->sortByName();
    }

    protected function getPatterns(array $extensions): array
    {
        return array_map(function (string $extension) {
            return '*.' . $extension;
        }, $extensions);
    }
}
