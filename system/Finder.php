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
        $finder = (new \Symfony\Component\Finder\Finder())
            ->files()
            ->in($this->path)
            ->notPath('#(^|/)_.+(/|$)#'); // ignore underscore files and folders

        $patterns = $this->getPatterns($this->extensions);
        if ($patterns === []) {
            // without extensions no file is a page, a name filter with an empty
            // list would not filter at all
            return $finder->name('#.^#');
        }

        return $finder->name($patterns)->sortByName(true);
    }

    protected function getPatterns(array $extensions): array
    {
        return array_map(fn(string $extension) => '*.' . $extension, $extensions);
    }
}
