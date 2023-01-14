<?php

declare(strict_types=1);

namespace herbie;

final class Finder
{
    /** @var array|string[]  */
    private array $extensions;    
    private string $path;

    public function __construct(array $options = [])
    {
        $this->setExtensions($options['extensions'] ?? []);
        $this->setPath($options['path'] ?? '');
    }
    
    private function setExtensions(array $extensions): void
    {
        $this->extensions = $extensions;
    }

    private function setPath(string $path): void
    {
        $this->path = $path;
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
