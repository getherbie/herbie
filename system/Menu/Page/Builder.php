<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Page;

use Herbie\Cache\CacheInterface;
use Herbie\Loader\FrontMatterLoader;
use Herbie\Menu\Page\Iterator\SortableIterator;
use Herbie\Menu\RecursiveFilterIterator;

class Builder
{

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
     * @param array $paths
     * @param array $extensions
     */
    public function __construct(array $paths, array $extensions)
    {
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
     * @return Collection
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
                            $item = $this->createItem($indexFile, $relPathname, $alias);
                            $collection->addItem($item);
                            break;
                        }
                        // other files
                    } else {
                        if (!$this->isValid($fileInfo->getPathname(), $fileInfo->getExtension())) {
                            continue;
                        }
                        $item = $this->createItem($fileInfo->getPathname(), $fileInfo->getRelativePathname(), $alias);
                        $collection->addItem($item);
                    }
                }

            }
            $this->storeCollection($collection);
        }
        return $collection;
    }

    /**
     * @return Collection
     */
    private function restoreCollection()
    {
        if (is_null($this->cache)) {
            return new Collection();
        }
        $collection = $this->cache->get(__CLASS__);
        if ($collection === false) {
            return new Collection();
        }
        return $collection;
    }

    /**
     * @param $collection
     * @return bool
     */
    private function storeCollection($collection)
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
        $directoryIterator = new \Herbie\Iterator\RecursiveDirectoryIterator($path);
        $callback = [new FileFilterCallback($this->extensions), 'call'];
        $filterIterator = new \RecursiveCallbackFilterIterator($directoryIterator, $callback);
        $mode = \RecursiveIteratorIterator::SELF_FIRST;
        $iteratorIterator = new \RecursiveIteratorIterator($filterIterator, $mode);
        return new SortableIterator($iteratorIterator, Iterator\SortableIterator::SORT_BY_NAME);
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
     * @param string $absolutePath
     * @param string $relativePath
     * @param string $alias
     * @return Item
     */
    protected function createItem($absolutePath, $relativePath, $alias)
    {
        $loader = new FrontMatterLoader();
        $data = $loader->load($absolutePath);

        $trimExtension = empty($data['keep_extension']);
        $route = $this->createRoute($relativePath, $trimExtension);

        $data['path'] = $alias . '/' . $relativePath;
        $data['route'] = $route;
        $item = new Item($data);

        if (empty($item->modified)) {
            $item->modified = date('c', filemtime($absolutePath));
        }
        if (empty($item->date)) {
            $item->date = date('c', filectime($absolutePath));
        }
        if (!isset($item->hidden)) {
            $item->hidden = !preg_match('/^[0-9]+-/', basename($relativePath));
        }
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
        foreach ($segments as $i => $segment) {
            $segments[$i] = preg_replace('/^[0-9]+-/', '', $segment);
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
