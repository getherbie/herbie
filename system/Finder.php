<?php

declare(strict_types=1);

namespace herbie;

final readonly class Finder
{
    /** @var string[]  */
    private array $extensions;
    private string $path;

    public function __construct(array $options = [])
    {
        $this->extensions = $options['extensions'] ?? [];
        $this->path = $options['path'] ?? '';
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
