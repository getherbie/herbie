<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Url;

use Herbie\Exception\ResourceNotFoundException;
use Herbie\Menu\Page;
use Herbie\Menu\Post;

/**
 * The URLMatcher matches a given route and returns the path to a valid page
 * or post file.
 */
class UrlMatcher
{
    /**
     * @var PageMenuCollection Collection of all pages.
     */
    protected $pages;

    /**
     * @var Post\Collection Collection of all posts.
     */
    protected $posts;

    /**
     * Constructor
     * @param Page\Collection $collection Collection of all pages
     * @param Post\Collection $posts Collection of all posts
     */
    public function __construct(Page\Collection $pages, Post\Collection $posts)
    {
        $this->pages = $pages;
        $this->posts = $posts;
    }

    /**
     * Returns a path to a valid page or post file.
     * @param string $route The route of the current request.
     * @return \Herbie\Menu\ItemInferface The path to a page or post file.
     * @throws ResourceNotFoundException
     */
    public function match($route)
    {
        // Page
        $item = $this->pages->getItem($route);
        if (isset($item)) {
            return $item;
        }

        // Post
        $item = $this->posts->getItem($route);
        if (isset($item)) {
            return $item;
        }

        // Blog main page
        $blogRoute = $this->getBlogRoute();
        if (0 === strpos($route, $blogRoute)) {
            $item = $this->pages->getItem($blogRoute);
            if (isset($item)) {
                $filteredItems = $this->posts->filterItems();
                if (!empty($filteredItems)) {
                    return $item;
                }
            }
        }
        throw new ResourceNotFoundException('Page "' . $route . '" not found.', 404);
    }

    /**
     * Returns the route to the website blog.
     * @return string The route to the blog.
     */
    protected function getBlogRoute()
    {
        return $this->posts->getBlogRoute();
    }
}
