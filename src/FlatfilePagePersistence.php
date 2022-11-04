<?php

declare(strict_types=1);

namespace herbie;

/**
 * Loads the whole page.
 */
final class FlatfilePagePersistence implements PagePersistenceInterface
{
    private Alias $alias;

    private Config $config;

    public function __construct(Alias $alias, Config $config)
    {
        $this->alias = $alias;
        $this->config = $config;
    }

    /**
     * @param string $id The aliased unique path to the file (i.e. @page/about/company.md)
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
        $path = $this->config->getAsString('paths.pages');
        $extensions = str_explode_filtered($this->config->getAsString('fileExtensions.pages'), ',');

        $recDirectoryIt = new RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $callback = new FileInfoFilterCallback($extensions);
        $recCallbackFilterIt = new \RecursiveCallbackFilterIterator($recDirectoryIt, $callback);

        $recIteratorIt = new \RecursiveIteratorIterator($recCallbackFilterIt);
        $sortIt = new FileInfoSortableIterator($recIteratorIt, FileInfoSortableIterator::SORT_BY_NAME);

        $items = [];

        /** @var FileInfo[] $sortIt */
        foreach ($sortIt as $fileInfo) {
            try {
                $items[] = $this->readFile($fileInfo->getAliasedPathname());
            } catch (\Exception $e) {
            }
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

        [$yaml, $segments] = $this->parseFileContent($content);

        $data = Yaml::parse($yaml);

        if (!isset($data['format'])) {
            $data['format'] = pathinfo($path, PATHINFO_EXTENSION);
        }

        if (!isset($data['path'])) {
            $data['path'] = $alias;
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
            $trimExtension = empty($data['keep_extension']);
            $data['route'] = $this->createRoute($alias, $trimExtension);
        }

        return [
            'id' => $alias,
            'parent' => '', // TODO determine parent
            'data' => $data,
            'segments' => $segments
        ];
    }

    private function parseFileContent(string $content): array
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
