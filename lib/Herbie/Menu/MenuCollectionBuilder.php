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
     * @var Parser
     */
    protected $parser;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @param Parser $parser
     * @param CacheInterface $cache
     */
    public function __construct(Parser $parser, CacheInterface $cache)
    {
        $this->parser = $parser;
        $this->cache = $cache;
    }

    /**
     * @param string $path
     * @return MenuCollection
     */
    public function build($path)
    {
        $realpath = realpath($path);
        $items = $this->cache->get(__CLASS__);
        if ($items === false) {

            $collection = new MenuCollection();

            if (is_dir($realpath)) {

                $objects = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($realpath), RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($objects AS $path => $splFileInfo) {

                    if (substr($splFileInfo->getFileName(), 0, 1) == '.') {
                        continue;
                    }

                    if ($splFileInfo->isFile()) {

                        $loader = new FrontMatterLoader($this->parser);
                        $data = $loader->load($path);

                        $trimExtension = empty($data['preserveExtension']);

                        $data['type'] = 'file';
                        $data['path'] = $path;
                        $data['route'] = $this->createRoute($path, $realpath, $trimExtension);
                        $data['depth'] = $objects->getDepth() + 1;
                        $item = new MenuItem($data);

                    } else {

                        $data = [];
                        $data['type'] = 'folder';
                        $data['path'] = $path;
                        $data['route'] = $this->createRoute($path, $realpath, $trimExtension);
                        $data['depth'] = $objects->getDepth() + 1;
                        $item = new MenuItem($data);

                        $configPath = $path . '/folder.yml';
                        if (is_file($configPath)) {
                            $folderConf = $this->parser->parse(file_get_contents($configPath));
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
     * @param string $realpath
     * @param bool $trimExtension
     * @return string
     */
    protected function createRoute($path, $realpath, $trimExtension=false)
    {
        $route = str_replace($realpath, '', $path);
        $segments = explode('/', $route);
        foreach ($segments AS $i => $segment) {
            $segments[$i] = preg_replace('/^[0-9]+-/', '', $segment);
        }
        $imploded = implode('/', $segments);

        // trim extension
        $pos = strrpos($imploded, '.');
        if ($trimExtension && ($pos !== false)) {
            $imploded = substr($imploded, 0, $pos);
        }

        return trim($imploded, '/');
    }

}
