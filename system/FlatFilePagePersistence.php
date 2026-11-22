<?php

declare(strict_types=1);

namespace herbie;

use Exception;
use Psr\SimpleCache\CacheInterface;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\SplFileInfo;
use UnexpectedValueException;

if (!defined('UTF8_BOM')) {
    define('UTF8_BOM', chr(0xEF) . chr(0xBB) . chr(0xBF));
}

/**
 * Loads the whole page.
 */
final class FlatFilePagePersistence implements PagePersistenceInterface
{
    private readonly bool $cacheEnable;
    private readonly int $cacheTTL;
    /** @var array<int, string>|null */
    private ?array $parentsCache = null;

    public function __construct(
        private readonly Alias $alias,
        private readonly CacheInterface $cache,
        private readonly Finder $finder,
        array $options = [],
    ) {
        $this->cacheEnable = (bool)($options['cache'] ?? false);
        $this->cacheTTL = (int)($options['cacheTTL'] ?? 0);
    }

    /**
     * @param string $id The aliased unique path to the file (i.e. @page/1-about/2-company.md)
     */
    #[\Override]
    public function findById(string $id): ?array
    {
        try {
            $root = $this->alias->get('@page');
            $rootReal = realpath($root);
            $idReal = realpath($id);
            // Prevent path traversal outside of the page root.
            if (
                $rootReal === false || $idReal === false
                || !str_starts_with($idReal, $rootReal . DIRECTORY_SEPARATOR)
            ) {
                return null;
            }
            $relativePathName = str_replace($root, '', $id);
            $relativePath = dirname($relativePathName);
            $fileInfo = new SplFileInfo($id, ltrim($relativePath, '/'), ltrim($relativePathName, '/'));
            return $this->readFile($fileInfo);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readFile(SplFileInfo $fileInfo): array
    {
        $basename = basename($fileInfo->getBasename('.' . $fileInfo->getExtension()));
        $path = $fileInfo->getPathname();
        $relativePath = $fileInfo->getRelativePathname();
        $alias = '@page/' . $relativePath;

        $content = $fileInfo->getContents();

        [$yaml, $segments] = self::parseFileContent($content);

        $data = Yaml::parse($yaml);

        $data['format'] ??= pathinfo($path, PATHINFO_EXTENSION);

        $data['path'] ??= $path;

        $data['modified'] ??= date_format('c', file_mtime($path));

        if (!isset($data['date'])) {
            if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}).*$/', $basename, $matches)) {
                $data['date'] = date_format('c', time_from_string($matches[1]));
            } else {
                $data['date'] = date_format('c', file_mtime($path));
            }
        }

        $data['hidden'] ??= !preg_match('/^[0-9]+-/', $basename);

        if (!isset($data['route'])) {
            $trimExtension = empty($data['keep_extension']);
            $data['route'] = $this->createRoute($alias, $trimExtension);
        }

        $data['parent_route'] ??= trim(dirname($data['route']), '.');

        $data['id'] ??= $alias;

        $data['parent_id'] ??= $this->determineParentId($alias);

        return [
            'data' => $data,
            'segments' => $segments,
        ];
    }

    public static function parseFileContent(string $content): array
    {
        $yaml = '';
        $segments = [];

        $matched = preg_match('/^[' . UTF8_BOM . ']*-{3}\r?\n(.*?)\r?\n-{3}\R(.*)/s', $content, $matches);

        if ($matched === 1 && count($matches) === 3) {
            $yaml = $matches[1];

            $content = $matches[2];

            // Protect fenced code blocks so that segment separators (--- name ---)
            // inside them are not interpreted as content segment boundaries.
            $fencedBlocks = [];
            $protected = preg_replace_callback(
                '/(^`{3,}[^\\n]*\\n[\\s\\S]*?^`{3,})|(^~{3,}[^\\n]*\\n[\\s\\S]*?^~{3,})/m',
                static function (array $match) use (&$fencedBlocks): string {
                    $fencedBlocks[] = $match[0];
                    return '@@CODEBLOCK' . (count($fencedBlocks) - 1) . '@@';
                },
                $content,
            );
            $protected ??= $content;

            $splitContent = preg_split('/^-{3} (.+) -{3}\\R?$/m', $protected, -1, PREG_SPLIT_DELIM_CAPTURE);
            if ($splitContent === false) {
                throw new UnexpectedValueException('Error at reading file content');
            }

            $count = count($splitContent);
            if ($count % 2 === 0) {
                throw new UnexpectedValueException('Error at reading file content');
            }

            $segments['default'] = array_shift($splitContent);
            $splitContentCount = count($splitContent);
            for ($i = 0; $i < $splitContentCount; $i = $i + 2) {
                $key = $splitContent[$i];
                $value = $splitContent[$i + 1];
                if (array_key_exists($key, $segments)) {
                    $segments[$key] .= $value;
                } else {
                    $segments[$key] = $value;
                }
            }

            // Restore protected fenced code blocks in every segment.
            foreach ($segments as $key => $segment) {
                $segments[$key] = preg_replace_callback(
                    '/@@CODEBLOCK(\\d+)@@/',
                    static function (array $match) use ($fencedBlocks): string {
                        $index = (int)$match[1];
                        return $fencedBlocks[$index] ?? $match[0];
                    },
                    (string)$segment,
                );
            }

            $i = 0;
            $last = count($segments) - 1;
            foreach ($segments as $key => $segment) {
                /** @var string $segment */
                if ($i === $last) {
                    $segments[$key] = $segment;
                } else {
                    $replaced = preg_replace('/\R?$/', '', $segment, 1);
                    if ($replaced !== null) {
                        $segments[$key] = $replaced;
                    }
                }
                $i++;
            }
        }

        return [$yaml, $segments];
    }

    private function createRoute(string $path, bool $trimExtension = false): string
    {
        $route = str_unleading_slash($path);

        $segments = str_explode_filtered($route, '/');

        if (isset($segments[0])) {
            if (str_starts_with($segments[0], '@')) {
                unset($segments[0]);
            }
        }

        foreach ($segments as $i => $segment) {
            /** @var string $segment */
            if (!preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}).*$/', $segment)) {
                $segments[$i] = preg_replace('/^[0-9]+-/', '', $segment);
            }
        }
        $imploded = implode('/', $segments);

        // trim extension
        $pos = strrpos($imploded, '.');
        if ($trimExtension && ($pos !== false)) {
            $imploded = substr($imploded, 0, $pos);
        }

        // remove last "/index" from route
        $route = preg_replace('#\/index$#', '', trim($imploded, '\/'));

        // handle index route
        return ($route === null || $route === 'index') ? '' : $route;
    }

    private function determineParentId(string $alias): string
    {
        $parents = $this->findParents($this->alias->get('@page'), '@page');
        $filename = $this->getFilenameWithoutPrefix($alias);

        if ($filename === 'index') {
            $segment = dirname($alias, 2);
        } else {
            $segment = dirname($alias);
        }

        if ($segment === '.') {
            return '';
        }

        $segmentDepth = substr_count($segment, '/');

        foreach ($parents as $parent) {
            if (!str_starts_with($parent, $segment)) {
                continue;
            }
            $parentDepth = substr_count($parent, '/');
            if ($parentDepth > ($segmentDepth + 1)) {
                continue;
            }
            $basename = basename($parent);
            if (preg_match('/^([0-9]+[-]+)?index\.[A-Za-z0-9]+$/', $basename) === 1) {
                return $parent;
            }
        }

        return '';
    }

    private function findParents(string $folder, string $alias): array
    {
        if ($this->parentsCache !== null) {
            return $this->parentsCache;
        }
        $parents = [];
        foreach ($this->finder->pageFiles() as $file) {
            $filename = $file->getFilenameWithoutExtension();
            $filename = preg_replace('/^([0-9]+[-]+)?(.+)$/', '\\2', $filename);
            if ($filename === 'index') {
                $parents[] = str_replace($folder, $alias, $file->getPathname());
            }
        }
        sort($parents);
        return $this->parentsCache = $parents;
    }

    public function clearParentsCache(): void
    {
        $this->parentsCache = null;
    }

    private function getFilenameWithoutPrefix(string $filepath): string
    {
        $filename = pathinfo($filepath, PATHINFO_FILENAME);
        return (string)preg_replace('/^([0-9]+[-]+)(.+)$/', '\\2', $filename);
    }

    #[\Override]
    public function findAll(): array
    {
        $items = [];

        $useCache = $this->cacheEnable && $this->cacheTTL > 0;
        $baseCacheId = 'pagelist_' . md5(__METHOD__);
        $modified = $useCache ? $this->finder->pagesModified() : 0;
        $cacheId = $baseCacheId . '_' . $modified;

        if ($useCache) {
            $cached = $this->cache->get($cacheId);
            if (is_array($cached)) {
                return $cached;
            }
        }

        foreach ($this->finder->pageFiles() as $page) {
            $items[] = $this->readFile($page);
        }

        if ($useCache) {
            $previous = $this->cache->get($baseCacheId . '_previous');
            if (is_string($previous) && $previous !== $cacheId) {
                $this->cache->delete($previous);
            }
            $this->cache->set($baseCacheId . '_previous', $cacheId, $this->cacheTTL);
            $this->cache->set('herbie_pages_modified', $modified, $this->cacheTTL);
            $this->cache->set($cacheId, $items, $this->cacheTTL);
        }

        return $items;
    }
}
