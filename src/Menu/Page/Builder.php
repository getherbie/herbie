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

use Herbie\Application;
use Herbie\Cache\CacheInterface;
use Herbie\Loader\FrontMatterLoader;
use Herbie\Menu\RecursiveFilterIterator;

class Builder
{

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $extensions;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->cache = $app['dataCache'];
        $this->path = $app['config']->get('pages.path');
        $this->extensions = $app['config']->get('pages.extensions');
    }

    /**
     * @param string $path
     * @return Collection
     */
    public function buildCollection($path = null)
    {
        if(is_null($path)) {
            $path = $this->path;
        }
        $items = $this->cache->get(__CLASS__);
        if ($items === false) {

            $collection = new Collection();
            $realpath = realpath($path);
            if (is_dir($realpath)) {

                $dirItr = new \RecursiveDirectoryIterator($realpath);
                $filterItr = new FileFilter($dirItr);
                $mode = \RecursiveIteratorIterator::SELF_FIRST;
                $objects = new \RecursiveIteratorIterator($filterItr, $mode);

                foreach ($objects as $path => $splFileInfo) {

                    if ($splFileInfo->isFile()) {

                        if (!in_array($splFileInfo->getExtension(), $this->extensions)) {
                            continue;
                        }

                        $loader = new FrontMatterLoader();
                        $data = $loader->load($path);

                        $trimExtension = empty($data['preserveExtension']);
                        $route = $this->createRoute($path, $realpath, $trimExtension);

                        $data['path'] = $path;
                        $data['route'] = $route;
                        $data['depth'] = substr_count($route, '/') + 1;
                        $item = new Item($data);

                        if (empty($item->date)) {
                            $item->date = date('c', $splFileInfo->getCTime());
                        }
                        $item->hidden = !preg_match('/^[0-9]+-/', $splFileInfo->getBasename());

                        $collection->addItem($item);

                    }
                }
            }
            $this->cache->set(__CLASS__, $items);
        }

        // Sort
        $collection->sort(function ($a, $b) {
            return strcmp($a->path, $b->path);
        });

        return $collection;
    }

    /**
     * @param string $path
     * @param string $realpath
     * @param bool $trimExtension
     * @return string
     */
    protected function createRoute($path, $realpath, $trimExtension = false)
    {
        $route = str_replace($realpath, '', $path);
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
        #return trim($imploded, '/');
        $route = preg_replace('#\/index$#', '', trim($imploded, '/'));
        return ($route == 'index') ? '' : $route;
    }
}
