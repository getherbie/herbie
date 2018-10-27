<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Post;

use Herbie\Cache\CacheInterface;
use Herbie\Config;
use Herbie\Helper\PathHelper;
use Herbie\Loader\FrontMatterLoader;

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
     * @var string
     */
    protected $blogRoute;

    /**
     * @param CacheInterface $cache
     * @param Config $config
     */
    public function __construct(CacheInterface $cache, Config $config)
    {
        $this->cache = $cache;
        $this->path = $config->get('posts.path');
        $this->extensions = $config->get('posts.extensions');
        $this->blogRoute = $config->get('posts.blog_route');
    }

    /**
     * @param string $path
     * @return Collection
     */
    public function build($path = null)
    {
        if (is_null($path)) {
            $path = $this->path;
        }
        $collection = $this->cache->get(__CLASS__);
        if ($collection === false) {
            $realpath = realpath($path);
            $collection = new Collection($this->blogRoute);
            if (is_readable($realpath)) {
                $loader = new FrontMatterLoader();
                foreach (scandir($realpath, 1) as $filename) {
                    if (in_array(substr($filename, 0, 1), ['.', '_'])) {
                        continue;
                    }
                    $pathinfo = pathinfo($filename);
                    if (!in_array($pathinfo['extension'], $this->extensions)) {
                        continue;
                    }
                    $data = $loader->load($realpath.'/'.$filename);
                    if (empty($data['modified'])) {
                        $data['modified'] = date('c', filemtime($realpath.'/'.$filename));
                    }
                    if (empty($data['date'])) {
                        $data['date'] = PathHelper::extractDateFromPath($filename);
                    }
                    $data['path'] = '@post/'.$filename;
                    $data['blogRoute'] = $this->blogRoute;
                    $item = new Item($data);
                    $collection->addItem($item);
                }
            }
            $this->cache->set(__CLASS__, $collection);
        }
        return $collection;
    }
}
