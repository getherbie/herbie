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
use Herbie\Menu\Page\Iterator\SortableIterator;
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
     * @var array
     */
    protected $indexFiles;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->cache = $app['dataCache'];
        $this->path = realpath($app['config']->get('pages.path'));
        $this->extensions = (array) $app['config']->get('pages.extensions');
        $this->indexFiles = [];
    }

    /**
     * @param string $path
     * @return Collection
     */
    public function buildCollection($path = null)
    {
        if (isset($path)) {
            $this->path = realpath($path);
        }

        $collection = $this->cache->get(__CLASS__);
        if ($collection === false) {

            $collection = new Collection();
            if (is_dir($this->path)) {

                $this->indexFiles = [];

                // recursive iterators
                $directoryIterator = new \RecursiveDirectoryIterator($this->path);
                $callback = [new FileFilterCallback($this->extensions), 'call'];
                $filterIterator = new \RecursiveCallbackFilterIterator($directoryIterator, $callback);
                $mode = \RecursiveIteratorIterator::SELF_FIRST;
                $iteratorIterator = new \RecursiveIteratorIterator($filterIterator, $mode);
                $sit = new SortableIterator($iteratorIterator, Iterator\SortableIterator::SORT_BY_NAME);

                foreach ($sit as $path => $fileInfo) {
                    // index file as describer for parent folder
                    if ($fileInfo->isDir()) {
                        // get first index file only
                        foreach (glob($path . '/*index.*') as $indexFile) {
                            $this->indexFiles[] = $indexFile;
                            $item = $this->createItem($indexFile);
                            $collection->addItem($item);
                            break;
                        }
                    // other files
                    } else {
                        if (!$this->isValid($path, $fileInfo->getExtension())) {
                            continue;
                        }
                        $item = $this->createItem($path);
                        $collection->addItem($item);
                    }
                }
            }
            $this->cache->set(__CLASS__, $collection);
        }

        return $collection;
    }

    /**
     * @param string $path
     * @param string $extension
     * @return boolean
     */
    protected function isValid($path, $extension)
    {
        if (!in_array($extension, $this->extensions)) {
            return false;
        }
        if (in_array($path, $this->indexFiles)) {
            return false;
        }
        return true;
    }

    /**
     *
     * @param string $path
     * @return \Herbie\Menu\Page\Item
     */
    protected function createItem($path)
    {
        $loader = new FrontMatterLoader();
        $data = $loader->load($path);

        $trimExtension = empty($data['preserveExtension']);
        $route = $this->createRoute($path, $trimExtension);

        $data['path'] = $path;
        $data['route'] = $route;
        $item = new Item($data);

        if (empty($item->date)) {
            $item->date = date('c', filectime($path));
        }
        $item->hidden = !preg_match('/^[0-9]+-/', basename($path));
        return $item;
    }

    /**
     * @param string $path
     * @param bool $trimExtension
     * @return string
     */
    protected function createRoute($path, $trimExtension = false)
    {
        $route = str_replace($this->path, '', $path);
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
