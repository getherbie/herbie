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

namespace Herbie\Menu;

use Herbie\Config;
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
    private $flatfilePersistence;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $extensions;

    /**
     * @var array
     */
    private $indexFiles;

    /**
     * @var MenuFactory
     */
    private $menuFactory;

    /**
     * @param FlatfilePersistenceInterface $flatfilePersistence
     * @param Config $config
     * @param MenuFactory $menuFactory
     */
    public function __construct(
        FlatfilePersistenceInterface $flatfilePersistence,
        Config $config,
        MenuFactory $menuFactory
    ) {
        $this->flatfilePersistence = $flatfilePersistence;
        $this->path = $config['paths']['pages'];
        $this->extensions = $config['fileExtensions']['pages']->toArray();
        $this->indexFiles = [];
        $this->menuFactory = $menuFactory;
    }

    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * @return void
     */
    public function unsetCache(): void
    {
        $this->cache = null;
    }

    /**
     * @return MenuList
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function buildMenuList(): MenuList
    {
        $menuList = $this->restoreMenuList();
        if (!$menuList->fromCache) {
            $this->indexFiles = [];
            foreach ($this->getIterator($this->path) as $fileInfo) {
                // index file as describer for parent folder
                if ($fileInfo->isDir()) {
                    // get first index file only
                    foreach (glob($fileInfo->getPathname() . '/*index.*') as $indexFile) {
                        $this->indexFiles[] = $indexFile;
                        $relPathname = $fileInfo->getRelativePathname() . '/' . basename($indexFile);
                        $item = $this->createItem($relPathname, '@page');
                        $menuList->addItem($item);
                        break;
                    }
                    // other files
                } else {
                    if (!$this->isValid($fileInfo->getPathname(), $fileInfo->getExtension())) {
                        continue;
                    }
                    $item = $this->createItem($fileInfo->getRelativePathname(), '@page');
                    $menuList->addItem($item);
                }
            }
            $this->storeMenuList($menuList);
        }
        return $menuList;
    }

    /**
     * @return MenuList
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function restoreMenuList(): MenuList
    {
        if (is_null($this->cache)) {
            return $this->menuFactory->newMenuList();
        }
        $menuList = $this->cache->get(__CLASS__);
        if (is_null($menuList)) {
            return $this->menuFactory->newMenuList();
        }
        return $menuList;
    }

    /**
     * @param MenuList $menuList
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function storeMenuList(MenuList $menuList): bool
    {
        if (is_null($this->cache)) {
            return false;
        }
        $menuList->fromCache = true;
        return $this->cache->set(__CLASS__, $menuList);
    }


    /**
     * @param string $path
     * @return SortableIterator
     */
    private function getIterator(string $path) :SortableIterator
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
    private function isValid(string $absolutePath, string $extension): bool
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
    private function createItem(string $relativePath, string $alias): MenuItem
    {
        $page = $this->flatfilePersistence->findById($alias . '/' . $relativePath);

        // determine route here because we need the relative path for this
        if (!isset($page['data']['route'])) {
            $trimExtension = empty($page['data']['keep_extension']);
            $page['data']['route'] = $this->createRoute($relativePath, $trimExtension);
        }

        $item = $this->menuFactory->newMenuItem($page['data']);
        return $item;
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
