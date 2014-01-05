<?php

namespace Herbie\Menu;

use Herbie\Cache\CacheInterface;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

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
        $this->path = $path;
        $this->cache = $cache;
    }

    /**
     * @return MenuCollection
     */
    public function build()
    {
        $items = $this->cache->get(__CLASS__);
        if ($items === false) {

            $realpath = realpath($this->path);
            $collection = new MenuCollection();

            if (is_dir($realpath)) {

                $objects = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($realpath), RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($objects AS $this->path => $splFileInfo) {

                    if (substr($splFileInfo->getFileName(), 0, 1) == '.') {
                        continue;
                    }

                    if ($splFileInfo->isFile()) {

                        $loader = new \Herbie\Loader\FrontMatterLoader();
                        $data = $loader->load($this->path);
                        $data['type'] = 'file';
                        $data['path'] = $this->path;
                        $data['route'] = $this->createRoute($realpath);
                        $data['depth'] = $objects->getDepth() + 1;
                        $item = new MenuItem($data);
                    } else {

                        $data = [];
                        $data['type'] = 'folder';
                        $data['path'] = $this->path;
                        $data['route'] = $this->createRoute($realpath);
                        $data['depth'] = $objects->getDepth() + 1;
                        $item = new MenuItem($data);

                        $configPath = $this->path . '/config.yml';
                        if (is_file($configPath)) {
                            $parser = new \Symfony\Component\Yaml\Parser();
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

        return $collection;
    }

    /**
     * @param string $realpath
     * @return string
     */
    protected function createRoute($realpath)
    {
        $route = str_replace($realpath, '', $this->path);
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
