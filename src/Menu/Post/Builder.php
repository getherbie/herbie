<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Post;

use Herbie\Application;
use Herbie\Cache\CacheInterface;
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
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->cache = $app['dataCache'];
        $this->path = $app['config']->get('posts.path');
        $this->extensions = $app['config']->get('posts.extensions');
        $this->blogRoute = $app['config']->get('posts.blog_route');
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
                    if (empty($data['date'])) {
                        $data['date'] = $this->extractDateFromPath($filename);
                    }
                    $data['path'] = '@post/'.$filename;
                    $data['blogRoute'] = $this->blogRoute;
                    $item = new Item($data);
                    $collection->addItem($item);
                }
            }
            $this->cache->set(__CLASS__, $collection);
        }
        #echo"<pre>";print_r($collection);echo"</pre>";
        return $collection;
    }

    /**
     * @param string $path
     * @return string
     * @todo Duplicate code in Herbie\Loader\PageLoader
     */
    protected function extractDateFromPath($path)
    {
        $filename = basename($path);
        preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}).*$/', $filename, $matches);
        return $matches[1];
    }
}
