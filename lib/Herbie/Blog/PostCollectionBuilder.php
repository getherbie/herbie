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
use Symfony\Component\Yaml\Parser;

class PostCollectionBuilder
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
     * @return PostCollection
     */
    public function build($path)
    {
        $realpath = realpath($path);
        $collection = $this->cache->get(__CLASS__);
        if($collection === false) {
            $collection = new PostCollection();
            if(is_dir($realpath)) {

                $loader = new FrontMatterLoader($this->parser);
                foreach(scandir($realpath, 1) AS $filename) {
                    if (substr($filename, 0, 1) == '.') {
                        continue;
                    }
                    $data = $loader->load($realpath.'/'.$filename);
                    $data['path'] = $realpath.'/'.$filename;
                    $item = new PostItem($data);
                    $collection->addItem($item);
                }

            }
            $this->cache->set(__CLASS__, $collection);
        }
        return $collection;
    }

}