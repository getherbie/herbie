<?php

declare(strict_types=1);

namespace herbie;

use Psr\SimpleCache\CacheInterface;

/**
 * Loads the whole page.
 */
final class FlatFilePagePersistence implements PagePersistenceInterface
{
    private Alias $alias;
    private CacheInterface $cache;
    private FlatFileIterator $flatFileIterator;
    private bool $cacheEnable;
    private int $cacheTTL;

    public function __construct(
        Alias $alias,
        CacheInterface $cache,
        FlatFileIterator $flatFileIterator,
        array $options = []
    ) {
        $this->alias = $alias;
        $this->cache = $cache;
        $this->flatFileIterator = $flatFileIterator;
        $this->cacheEnable = (bool)($options['cache'] ?? false);
        $this->cacheTTL = (int)($options['cacheTTL'] ?? 0);
    }

    /**
     * @param string $id The aliased unique path to the file (i.e. @page/1-about/2-company.md)
     */
    public function findById(string $id): ?array
    {
        try {
            return $this->readFile($id);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function findAll(): array
    {
        $items = [];

        if ($this->cacheEnable && $this->cacheTTL > 0) {
            $cached = $this->cache->get(__METHOD__);
            if (is_array($cached)) {
                return $cached;
            }
        }

        foreach ($this->flatFileIterator as $fileInfo) {
            try {
                /** @var FileInfo $fileInfo */
                $aliasedPath = '@page/' . $fileInfo->getRelativePathname();
                $items[] = $this->readFile($aliasedPath);
            } catch (\Exception $e) {
            }
        }

        if ($this->cacheEnable && $this->cacheTTL > 0) {
            $this->cache->set(__METHOD__, $items, $this->cacheTTL);
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function readFile(string $alias): array
    {
        $path = $this->alias->get($alias);
        $basename = basename($path);

        $content = file_read($path);

        [$yaml, $segments] = self::parseFileContent($content);

        $data = Yaml::parse($yaml);

        if (!isset($data['format'])) {
            $data['format'] = pathinfo($path, PATHINFO_EXTENSION);
        }

        if (!isset($data['path'])) {
            $data['path'] = $path;
        }

        if (!isset($data['modified'])) {
            $data['modified'] = date_format('c', file_mtime($path));
        }

        if (!isset($data['date'])) {
            if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}).*$/', $basename, $matches)) {
                $data['date'] = date_format('c', time_from_string($matches[1]));
            } else {
                $data['date'] = date_format('c', file_mtime($path));
            }
        }

        if (!isset($data['hidden'])) {
            $data['hidden'] = !preg_match('/^[0-9]+-/', $basename);
        }

        if (!isset($data['route'])) {
            $trimExtension = empty($data['keepExtension']);
            $data['route'] = $this->createRoute($alias, $trimExtension);
        }

        if (!isset($data['parentRoute'])) {
            $data['parentRoute'] = trim(dirname($data['route']), '.');
        }

        if (!isset($data['id'])) {
            $data['id'] = $alias;
        }

        if (!isset($data['parentId'])) {
            $data['parentId'] = $this->determineParentId($alias);
        }

        return [
            'data' => $data,
            'segments' => $segments
        ];
    }

    private function determineParentId(string $alias): string
    {
        $parents = $this->findParents($this->alias->get('@page'), '@page');
        $filename = $this->getFilenameWithoutPrefix($alias);

        if ($filename === 'index') {
            $segment = dirname(dirname($alias));
        } else {
            $segment = dirname($alias);
        }

        if ($segment === '.') {
            return '';
        }

        $segmentDepth = substr_count($segment, '/');

        foreach ($parents as $parent) {
            if (strpos($parent, $segment) !== 0) {
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
        static $parents;
        if (!isset($parents)) {
            $parents = [];
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder));
            foreach ($iterator as $file) {
                if (!$file->isDir()) {
                    $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                    $filename = preg_replace('/^([0-9]+[-]+)?(.+)$/', '\\2', $filename);
                    if ($filename === 'index') {
                        $parents[] = str_replace($folder, $alias, $file->getPathname());
                    }
                }
            }
            sort($parents);
        }
        return $parents;
    }

    private function getFilenameWithoutPrefix(string $filepath): string
    {
        $filename = pathinfo($filepath, PATHINFO_FILENAME);
        return (string)preg_replace('/^([0-9]+[-]+)(.+)$/', '\\2', $filename);
    }

    public static function parseFileContent(string $content): array
    {
        if (!defined('UTF8_BOM')) {
            define('UTF8_BOM', chr(0xEF) . chr(0xBB) . chr(0xBF));
        }

        $yaml = '';
        $segments = [];

        $matched = preg_match('/^[' . UTF8_BOM . ']*-{3}\r?\n(.*)\r?\n-{3}\R(.*)/ms', $content, $matches);

        if ($matched === 1 && count($matches) === 3) {
            $yaml = $matches[1];

            $splitContent = preg_split('/^-{3} (.+) -{3}\R?$/m', $matches[2], -1, PREG_SPLIT_DELIM_CAPTURE);
            if ($splitContent === false) {
                throw new \UnexpectedValueException('Error at reading file content');
            }

            $count = count($splitContent);
            if ($count % 2 === 0) {
                throw new \UnexpectedValueException('Error at reading file content');
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
            if (substr($segments[0], 0, 1) === '@') {
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
}
