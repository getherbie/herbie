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

use Herbie\Menu\ItemInterface;
use Herbie\Menu\ItemTrait;

class Item implements ItemInterface
{

    use ItemTrait;

    /**
     * @var array
     */
    protected $categories = [];

    /**
     * @var array
     */
    protected $tags = [];

    /**
     * @var array
     */
    protected $authors = [];

    /**
     * @param string $author
     * @return string
     */
    public function getAuthor($author)
    {
        foreach ($this->authors as $a) {
            if (strtolower($a) == strtolower($author)) {
                return $a;
            }
        }
        return '';
    }

    /**
     * @return array
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * @param string $category
     * @return string
     */
    public function getCategory($category)
    {
        foreach ($this->categories as $c) {
            if (strtolower($c) == strtolower($category)) {
                return $c;
            }
        }
        return '';
    }

    /**
     * @return array
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        $route = trim(basename($this->path), '/');
        $route = preg_replace('/^([0-9]{4})-([0-9]{2})-([0-9]{2})(.*)$/', '\\1/\\2/\\3\\4', $route);

        // Endung entfernen
        $pos = strrpos($route, '.');
        if ($pos !== false) {
            $route = substr($route, 0, $pos);
        }

        if (empty($this->blogRoute)) {
            return $route;
        }
        return $this->blogRoute . '/' . $route;
    }

    /**
     * @param string $tag
     * @return string
     */
    public function getTag($tag)
    {
        foreach ($this->tags as $t) {
            if (strtolower($t) == strtolower($tag)) {
                return $t;
            }
        }
        return '';
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param string $route
     * @return bool
     */
    public function routeEquals($route)
    {
        return $this->getRoute() == $route;
    }

    /**
     * @param array $authors
     */
    public function setAuthors($authors)
    {
        $this->authors = array_unique($authors);
    }

    /**
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->authors[] = $author;
    }

    /**
     * @param string $author
     * @return boolean
     */
    public function hasAuthor($author)
    {
        foreach ($this->authors as $c) {
            if (strtolower($c) == strtolower($author)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $category
     * @return boolean
     */
    public function hasCategory($category)
    {
        foreach ($this->categories as $c) {
            if (strtolower($c) == strtolower($category)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $tag
     * @return boolean
     */
    public function hasTag($tag)
    {
        foreach ($this->tags as $t) {
            if (strtolower($t) == strtolower($tag)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $categories
     */
    public function setCategories($categories)
    {
        $this->categories = array_unique($categories);
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->categories[] = $category;
    }

    /**
     * @param array $tags
     */
    public function setTags($tags)
    {
        $this->tags = array_unique($tags);
    }

    /**
     * @param string $tag
     */
    public function setTag($tag)
    {
        $this->tags[] = $tag;
    }
}
