<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie;

/**
 * Loads the whole page.
 */
class FlatfilePagePersistence implements PagePersistenceInterface
{
    /**
     * @var Alias
     */
    private $alias;

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @param Alias $alias
     * @param Configuration $config
     */
    public function __construct(Alias $alias, Configuration $config)
    {
        $this->alias = $alias;
        $this->config = $config;
    }

    /**
     * @param string $id The aliased unique path to the file (i.e. @page/about/company.md)
     * @return array
     * @throws \Exception
     */
    public function findById(string $id): array
    {
        $data = $this->readFile($id);
        return $data;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function findAll(): array
    {
        $path = $this->config->paths->pages;
        $extensions = $this->config->fileExtensions->pages->toArray();

        $recDirectoryIt = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);

        $callback = function (\SplFileInfo $current, $key, \RecursiveDirectoryIterator $iterator) use ($extensions) {
            // Allow recursion
            if ($iterator->hasChildren()) {
                return true;
            }
            if (strpos($current->getFilename(), '.') === 0) {
                return false;
            }
            // Check for file extensions
            if (in_array($current->getExtension(), $extensions)) {
                return true;
            }
            return false;
        };

        $recCallbackFilterIt = new \RecursiveCallbackFilterIterator($recDirectoryIt, $callback);
        $recIteratorIt = new \RecursiveIteratorIterator($recCallbackFilterIt);
        $sortIt = new SortableIterator($recIteratorIt, SortableIterator::SORT_BY_NAME);

        $items = [];
        foreach ($sortIt as $fileInfo) {
            /** @var FileInfo $fileInfo */
            $data = $this->readFile($fileInfo->getPathname());
            if (!isset($data['data']['route'])) {
                $relPath = str_replace($path, '', $fileInfo->getPathname());
                $data['data']['route'] = $this->createRoute($relPath, true);
            }
            $items[] = $data;
        }

        return $items;
    }

    /**
     * @param string $alias
     * @param bool $addDefFields
     * @return array
     * @throws \Exception
     */
    private function readFile(string $alias, bool $addDefFields = true): array
    {
        $path = $this->alias->get($alias);
        $content = $this->readFileContent($path);
        list($yaml, $segments) = $this->parseFileContent($content);

        $data = Yaml::parse($yaml);

        if ($addDefFields) {
            $basename = basename($path);
            if (!isset($data['format'])) {
                $data['format'] = pathinfo($path, PATHINFO_EXTENSION);
            }
            if (!isset($data['path'])) {
                $data['path'] = $alias;
            }
            if (!isset($data['modified'])) {
                $data['modified'] = date('c', filemtime($path));
            }
            if (!isset($data['date'])) {
                if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}).*$/', $basename, $matches)) {
                    $data['date'] = date('c', strtotime($matches[1]));
                } else {
                    $data['date'] = date('c', filectime($path));
                }
            }
            if (!isset($data['hidden'])) {
                $data['hidden'] = (int)!preg_match('/^[0-9]+-/', $basename);
            }
        }

        return [
            'id' => $alias,
            'parent' => '', //str_replace('.', null$route)),
            'data' => $data,
            'segments' => $segments
        ];
    }

    /**
     * @param string $path
     * @return array
     */
    public function readFrontMatter(string $path): array
    {
        if (!defined('UTF8_BOM')) {
            define('UTF8_BOM', chr(0xEF).chr(0xBB).chr(0xBF));
        }

        $yaml = '';

        $fileObject = new \SplFileObject($path);

        $i = 0;
        foreach ($fileObject as $line) {
            // strip BOM from the beginning and \n and \r from end of line
            $line = rtrim(ltrim($line, UTF8_BOM), "\n\r");
            if (preg_match('/^---$/', $line)) {
                $i++;
                continue;
            }
            if ($i > 1) {
                break;
            }
            if ($i == 1) {
                // add PHP_EOL to end of line
                $yaml .= $line . PHP_EOL;
            }
        }

        unset($fileObject);

        return (array) Yaml::parse($yaml);
    }

    /**
     * @param string $content
     * @return array
     * @throws \Exception
     */
    private function parseFileContent(string $content): array
    {
        if (!defined('UTF8_BOM')) {
            define('UTF8_BOM', chr(0xEF).chr(0xBB).chr(0xBF));
        }
        
        $yaml = '';
        $segments = [];

        $matched = preg_match('/^['.UTF8_BOM.']*-{3}\r?\n(.*)\r?\n-{3}\R(.*)/ms', $content, $matches);

        if ($matched === 1 && count($matches) == 3) {
            $yaml = $matches[1];

            $splitted = preg_split('/^-{3} (.+) -{3}\R?$/m', $matches[2], -1, PREG_SPLIT_DELIM_CAPTURE);

            $count = count($splitted);
            if ($count %2 == 0) {
                throw new \Exception('Fehler beim Auslesen der Seite.');
            }

            $segments['default'] = array_shift($splitted);
            $ct_splitted = count($splitted);
            for ($i=0; $i<$ct_splitted; $i=$i+2) {
                $key = $splitted[$i];
                $value = $splitted[$i+1];
                if (array_key_exists($key, $segments)) {
                    $segments[$key] .= $value;
                } else {
                    $segments[$key] = $value;
                }
            }

            $i = 0;
            $last = count($segments) - 1;
            foreach ($segments as $key => $segment) {
                $segments[$key] = ($i == $last) ? $segment : preg_replace('/\R?$/', '', $segment, 1);
                $i++;
            }
        }

        return [$yaml, $segments];
    }

    /**
     * @param string $path
     * @return string
     * @throws HttpException
     */
    private function readFileContent(string $path): string
    {
        // suppress E_WARNING since we throw an exception on error
        $contents = @file_get_contents($path);
        if (false === $contents) {
            throw HttpException::notFound('File "' . $path . '" does not exist');
        }
        return $contents;
    }

    /**
     * @param string $contentDir
     * @param array $contentExt
     * @return array
     */
    public static function getRouteToIdMapping(string $contentDir, array $contentExt): array
    {
        $path = $contentDir;

        $di = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);

        /** @var FileInfo[] $it */
        $it = new \RecursiveIteratorIterator($di);

        $files = [];
        foreach ($it as $file) {
            if (in_array($file->getExtension(), $contentExt)) {
                $files[] = (string)$file;
            }
        }

        sort($files);

        $mapping = [];
        foreach ($files as $file) {
            $route = str_replace($path, '', $file);
            foreach ($contentExt as $ex) {
                $route = str_replace('.' . $ex, '', $route);
            }
            $route = preg_replace('/^([0-9])+-/', '', $route);
            $route = preg_replace('/\/[0-9]+-/', '/', $route);
            $route = trim($route, '/');

            if ($route === 'index') {
                $route = '';
            } else {
                $pos = strrpos($route, '/index');
                if ($pos !== false) {
                    $route = substr($route, 0, $pos);
                }
            }

            $mapping['@page' . str_replace($contentDir, '', $file)] = $route;
        }

        return $mapping;
    }


    /**
     * @param string $path
     * @param bool $trimExtension
     * @return string
     */
    private function createRoute(string $path, bool $trimExtension = false): string
    {
        // strip left unix AND windows dir separator
        $route = ltrim($path, '\/');

        // remove leading numbers (sorting) from url segments
        $segments = explode('/', $route);

        if (isset($segments[0])) {
            if (substr($segments[0], 0, 1) === '@') {
                unset($segments[0]);
            }
        }

        foreach ($segments as $i => $segment) {
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
        return ($route == 'index') ? '' : $route;
    }
}
