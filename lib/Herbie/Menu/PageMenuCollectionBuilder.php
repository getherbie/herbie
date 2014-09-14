<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu;

use Herbie\Cache\CacheInterface;
use Herbie\Loader\FrontMatterLoader;

class PageMenuCollectionBuilder
{

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var array
     */
    protected $extensions;

    /**
     * @param CacheInterface $cache
     * @param array $extensions
     */
    public function __construct(CacheInterface $cache, array $extensions = [])
    {
        $this->cache = $cache;
        $this->extensions = $extensions;
    }

    /**
     * @param string $path
     * @return PageMenuCollection
     */
    public function build($path)
    {
        $realpath = realpath($path);
        $items = $this->cache->get(__CLASS__);
        if ($items === false) {

            $collection = new PageMenuCollection();

            if (is_dir($realpath)) {

                $objects = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($realpath),
                    \RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($objects as $path => $splFileInfo) {

                    if (substr($splFileInfo->getFileName(), 0, 1) == '.') {
                        continue;
                    }

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
                        $item = new PageMenuItem($data);

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
