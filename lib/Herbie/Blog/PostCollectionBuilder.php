<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Blog;

use Herbie\Cache\CacheInterface;
use Herbie\Loader\FrontMatterLoader;

class PostCollectionBuilder
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
     * @return PostCollection
     */
    public function build()
    {
        $collection = $this->cache->get(__CLASS__);
        if($collection === false) {
            $collection = new PostCollection();
            if(is_dir($this->path)) {

                $loader = new FrontMatterLoader();
                foreach(scandir($this->path, 1) AS $filename) {
                    if (substr($filename, 0, 1) == '.') {
                        continue;
                    }
                    $data = $loader->load($this->path.'/'.$filename);
                    $data['path'] = $this->path.'/'.$filename;
                    $item = new PostItem($data);
                    $collection->addItem($item);
                }

            }
            $this->cache->set(__CLASS__, $collection);
        }
        return $collection;
    }

}