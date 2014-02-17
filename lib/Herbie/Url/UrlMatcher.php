<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Url;

use Herbie\Blog\PostCollection;
use Herbie\Exception\ResourceNotFoundException;
use Herbie\Menu\MenuCollection;

/**
 * The url matcher.
 */
class UrlMatcher
{

    /**
     * @var MenuCollection
     */
    protected $collection;

    /**
     * @var PostCollection
     */
    protected $posts;

    /**
     * @param MenuCollection $collection
     * @param PostCollection $posts
     */
    public function __construct(MenuCollection $collection, PostCollection $posts)
    {
        $this->collection = $collection;
        $this->posts = $posts;
    }

    /**
     * @param string $route
     * @return string
     * @throws ResourceNotFoundException
     */
    public function match($route)
    {
        // File
        $item = $this->collection->getItem($route);
        if(isset($item) && $item->isFile()) {
            return $item->getPath();
        }

        // Folder
        $item = $this->collection->getItem($route . '/index');
        if(isset($item) && $item->isFile()) {
            return $item->getPath();
        }

        // Post
        $item = $this->posts->getItem($route);
        if(isset($item)) {
            return $item->getPath();
        }

        // Blog main page
        $blogRoute = $this->getBlogRoute();
        $item = $this->collection->getItem($blogRoute);
        if(isset($item) && $item->isFile()) {
            $filteredItems = $this->posts->filterItems();
            if(!empty($filteredItems)) {
                return $item->getPath();
            }
        }

        throw new ResourceNotFoundException('Page "' . $route . '" not found.', 404);
    }

    /**
     * @return string
     */
    protected function getBlogRoute()
    {
        $blogRoute = $this->posts->getBlogRoute();
        return empty($blogRoute) ? 'index' : $blogRoute;
    }

}
