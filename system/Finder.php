<?php

declare(strict_types=1);

namespace herbie;

use SplFileInfo;

final class Finder
{
    /** @var array|string[]  */
    private array $mediaExtensions;
    private string $mediaPath;    
    /** @var array|string[]  */
    private array $pageExtensions;
    private string $pagePath;

    public function __construct(array $options = [])
    {
        $this->setMediaExtensions($options['mediaExtensions'] ?? []);
        $this->setMediaPath($options['mediaPath'] ?? '');        
        $this->setPageExtensions($options['pageExtensions'] ?? []);
        $this->setPagePath($options['pagePath'] ?? '');
    }

    private function setMediaExtensions(array $extensions): void
    {
        $this->mediaExtensions = $extensions;
    }

    private function setMediaPath(string $path): void
    {
        $this->mediaPath = $path;
    }

    private function setPageExtensions(array $extensions): void
    {
        $this->pageExtensions = $extensions;
    }

    private function setPagePath(string $path): void
    {
        $this->pagePath = $path;
    }
    
    public function mediaFiles(string $relDir = ''): \Symfony\Component\Finder\Finder
    {
        $depth = count(str_explode_filtered($relDir, '/'));
        $patterns = join('|', $this->mediaExtensions);
        return (new \Symfony\Component\Finder\Finder())
            //->files()
            ->in($this->mediaPath)
            ->path($relDir)
            ->notPath('#(^|/)_.+(/|$)#') // ignore underscore files and folders
            ->depth($depth)
            ->filter(static function (SplFileInfo $file) use ($patterns)  {
                return $file->isDir() || \preg_match('/\.(' . $patterns . ')$/', $file->getPathname());
            })                
            ->sortByType();        
    }

    public function pageFiles(): \Symfony\Component\Finder\Finder
    {
        $patterns = $this->getPatterns($this->pageExtensions);
        return (new \Symfony\Component\Finder\Finder())
            ->files()
            ->in($this->pagePath)
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
