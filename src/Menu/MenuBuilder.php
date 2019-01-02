<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu;

use Herbie\Menu\Iterator\FileFilterCallback;
use Herbie\Menu\Iterator\RecursiveDirectoryIterator;
use Herbie\Menu\Iterator\SortableIterator;
use Herbie\Persistence\FlatfilePersistenceInterface;
use Psr\SimpleCache\CacheInterface;

class MenuBuilder
{
    /**
     * @var FlatfilePersistenceInterface
     */
    protected $flatfilePersistence;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var array
     */
    protected $paths;

    /**
     * @var array
     */
    protected $extensions;

    /**
     * @var array
     */
    protected $indexFiles;

    /**
     * @param FlatfilePersistenceInterface $flatfilePersistence
     * @param array $paths
     * @param array $extensions
     */
    public function __construct(FlatfilePersistenceInterface $flatfilePersistence, array $paths, array $extensions)
    {
        $this->flatfilePersistence = $flatfilePersistence;
        $this->paths = $paths;
        $this->extensions = $extensions;
        $this->indexFiles = [];
    }

    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @return void
     */
    public function unsetCache()
    {
        $this->cache = null;
    }

    /**
     * @return MenuList
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function buildCollection()
    {
        $collection = $this->restoreCollection();
        if (!$collection->fromCache) {
            foreach ($this->paths as $alias => $path) {
                $this->indexFiles = [];
                foreach ($this->getIterator($path) as $fileInfo) {
                    // index file as describer for parent folder
                    if ($fileInfo->isDir()) {
                        // get first index file only
                        foreach (glob($fileInfo->getPathname() . '/*index.*') as $indexFile) {
                            $this->indexFiles[] = $indexFile;
                            $relPathname = $fileInfo->getRelativePathname() . '/' . basename($indexFile);
                            $item = $this->createItem($relPathname, $alias);
                            $collection->addItem($item);
                            break;
                        }
                        // other files
                    } else {
                        if (!$this->isValid($fileInfo->getPathname(), $fileInfo->getExtension())) {
                            continue;
                        }
                        $item = $this->createItem($fileInfo->getRelativePathname(), $alias);
                        $collection->addItem($item);
                    }
                }
            }
            $this->storeCollection($collection);
        }
        return $collection;
    }

    /**
     * @return MenuList
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function restoreCollection()
    {
        if (is_null($this->cache)) {
            return new MenuList();
        }
        $collection = $this->cache->get(__CLASS__);
        if (is_null($collection)) {
            return new MenuList();
        }
        return $collection;
    }

    /**
     * @param $collection
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function storeCollection($collection)
    {
        if (is_null($this->cache)) {
            return false;
        }
        $collection->fromCache = true;
        return $this->cache->set(__CLASS__, $collection);
    }


    /**
     * @param string $path
     * @return SortableIterator
     */
    protected function getIterator($path)
    {
        // recursive iterators
        $directoryIterator = new RecursiveDirectoryIterator($path);
        $callback = [new FileFilterCallback($this->extensions), 'call'];
        $filterIterator = new \RecursiveCallbackFilterIterator($directoryIterator, $callback);
        $mode = \RecursiveIteratorIterator::SELF_FIRST;
        $iteratorIterator = new \RecursiveIteratorIterator($filterIterator, $mode);
        return new SortableIterator($iteratorIterator, SortableIterator::SORT_BY_NAME);
    }

    /**
     * @param string $absolutePath
     * @param string $extension
     * @return boolean
     */
    protected function isValid($absolutePath, $extension)
    {
        if (!in_array($extension, $this->extensions)) {
            return false;
        }
        if (in_array($absolutePath, $this->indexFiles)) {
            return false;
        }
        return true;
    }

    /**
     * @param string $relativePath
     * @param string $alias
     * @return MenuItem
     */
    protected function createItem($relativePath, $alias)
    {
        $page = $this->flatfilePersistence->findById($alias . '/' . $relativePath);

        // determine route here because we need the relative path for this
        if (!isset($page['data']['route'])) {
            $trimExtension = empty($page['data']['keep_extension']);
            $page['data']['route'] = $this->createRoute($relativePath, $trimExtension);
        }

        $item = new MenuItem($page['data']);
        return $item;
    }

    /**
     * @param string $path
     * @param bool $trimExtension
     * @return string
     */
    protected function createRoute($path, $trimExtension = false)
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
