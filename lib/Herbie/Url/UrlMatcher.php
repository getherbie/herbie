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

use Herbie\Menu\MenuCollection;
use Herbie\Blog\PostCollection;
use Herbie\Exception\ResourceNotFoundException;

/**
 * The url matcher.
 *
 * @author Thomas Breuss <thomas.breuss@zephir.ch>
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
        // Folder
        $item = $this->collection->getItem($route);
        if(isset($item) && $item->isFile()) {
            return $item->getPath();
        }

        // File
        $item = $this->collection->getItem($route . '/index');
        if(isset($item) && $item->isFile()) {
            return $item->getPath();
        }

        // Post
        $item = $this->posts->getItem($route);
        if(isset($item)) {
            return $item->getPath();
        }

        throw new ResourceNotFoundException('Page "' . $route . '" not found.', 404);
    }

}
