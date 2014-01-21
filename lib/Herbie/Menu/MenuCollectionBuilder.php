<?php

/*
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
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Symfony\Component\Yaml\Parser;

class MenuCollectionBuilder
{

    /**
     * @var string
     */
    protected $path;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @param string $path
     * @param CacheInterface $cache
     */
    public function __construct($path, CacheInterface $cache)
    {
        $this->path = realpath($path);
        $this->cache = $cache;
    }

    /**
     * @return MenuCollection
     */
    public function build()
    {
        $items = $this->cache->get(__CLASS__);
        if ($items === false) {

            $collection = new MenuCollection();

            if (is_dir($this->path)) {

                $objects = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($this->path), RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($objects AS $path => $splFileInfo) {

                    if (substr($splFileInfo->getFileName(), 0, 1) == '.') {
                        continue;
                    }

                    if ($splFileInfo->isFile()) {

                        $loader = new FrontMatterLoader();
                        $data = $loader->load($path);
                        $data['type'] = 'file';
                        $data['path'] = $path;
                        $data['route'] = $this->createRoute($path);
                        $data['depth'] = $objects->getDepth() + 1;
                        $item = new MenuItem($data);

                    } else {

                        $data = [];
                        $data['type'] = 'folder';
                        $data['path'] = $path;
                        $data['route'] = $this->createRoute($path);
                        $data['depth'] = $objects->getDepth() + 1;
                        $item = new MenuItem($data);

                        $configPath = $path . '/config.yml';
                        if (is_file($configPath)) {
                            $parser = new Parser();
                            $folderConf = $parser->parse(file_get_contents($configPath));
                            $item->setData($folderConf);
                        } else {
                            $title = preg_replace('/^[0-9]+-/', '', $splFileInfo->getBasename());
                            $item->title = ucfirst($title);
                        }

                    }

                    if (empty($item->date)) {
                        $item->date = date('c', $splFileInfo->getCTime());
                    }
                    $item->hidden = !preg_match('/^[0-9]+-/', $splFileInfo->getBasename());

                    $collection->addItem($item);
                }
            }
            $this->cache->set(__CLASS__, $items);
        }

        // Sort
        $collection->sort(function ($a, $b) { return strcmp($a->path, $b->path); });

        return $collection;
    }

    /**
     * @param string $path
     * @return string
     */
    protected function createRoute($path)
    {
        $route = str_replace($this->path, '', $path);
        $segments = explode('/', $route);
        foreach ($segments AS $i => $segment) {
            $segments[$i] = preg_replace('/^[0-9]+-/', '', $segment);
        }
        $imploded = implode('/', $segments);

        // trim extension
        $pos = strrpos($imploded, '.');
        if ($pos !== false) {
            $imploded = substr($imploded, 0, $pos);
        }

        return trim($imploded, '/');
    }

}
