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

class PostMenuCollectionBuilder
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
     * @var string
     */
    protected $blogRoute;

    /**
     * @param CacheInterface $cache
     * @param array $extensions
     */
    public function __construct(CacheInterface $cache, array $options = [])
    {
        $this->cache = $cache;
        foreach ($options as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @param string $path
     * @return PostMenuCollection
     */
    public function build($path)
    {
        $realpath = realpath($path);
        $collection = $this->cache->get(__CLASS__);
        if ($collection === false) {
            $collection = new PostMenuCollection($this->blogRoute);
            if (is_dir($realpath)) {

                $loader = new FrontMatterLoader();
                foreach (scandir($realpath, 1) as $filename) {
                    if (substr($filename, 0, 1) == '.') {
                        continue;
                    }
                    $pathinfo = pathinfo($filename);
                    if (!in_array($pathinfo['extension'], $this->extensions)) {
                        continue;
                    }
                    $data = $loader->load($realpath.'/'.$filename);
                    $data['path'] = $realpath.'/'.$filename;
                    $data['blogRoute'] = $this->blogRoute;
                    $item = new PostMenuItem($data);
                    $collection->addItem($item);
                }

            }
            $this->cache->set(__CLASS__, $collection);
        }
        #echo"<pre>";print_r($collection);echo"</pre>";
        return $collection;
    }
}
