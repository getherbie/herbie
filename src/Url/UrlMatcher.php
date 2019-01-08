<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Herbie\Url;

use Herbie\Exception\HttpException;
use Herbie\Menu\MenuItem;
use Herbie\Menu\MenuList;

/**
 * The URLMatcher matches a given route and returns the path to a valid page file.
 */
class UrlMatcher
{
    /**
     * @var MenuList List of all pages.
     */
    private $menuList;

    /**
     * Constructor
     * @param MenuList $menuList List of all pages
     */
    public function __construct(MenuList $menuList)
    {
        $this->menuList = $menuList;
    }

    /**
     * Returns a path to a valid page file.
     * @param string $route The route of the current request.
     * @return MenuItem
     * @throws HttpException
     */
    public function match(string $route): MenuItem
    {
        // Page
        $item = $this->menuList->getItem($route);
        if (isset($item)) {
            return $item;
        }

        // Blog main page
        $blogRoute = $this->getBlogRoute();
        if (0 === strpos($route, $blogRoute)) {
            $item = $this->menuList->getItem($blogRoute);
            if (isset($item)) {
                $filteredItems = $this->menuList->filterItems($route);
                if (!empty($filteredItems)) {
                    return $item;
                }
            }
        }

        throw HttpException::notFound('Page "' . $route . '" not found');
    }

    /**
     * Returns the route to the website blog.
     * @return string The route to the blog.
     */
    private function getBlogRoute(): string
    {
        return 'blog';
    }
}
