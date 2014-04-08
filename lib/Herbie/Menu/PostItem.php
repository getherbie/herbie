<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu;

use Exception;
use LogicException;

class PostItem
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $date;

    /**
     * @var boolean
     */
    protected $hidden;

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
     * @var array
     */
    protected $data = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    /**
     * @param string $path
     * @return string
     */
    protected function extractDateFromPath($path)
    {
        $filename = basename($path);
        preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}).*$/', $filename, $matches);
        return $matches[1];
    }

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
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return boolean
     */
    public function getHidden()
    {
        return (bool) $this->hidden;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return (bool) !$this->hidden;
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
     * @param string $date
     */
    public function setDate($date)
    {
        $this->date = is_numeric($date) ? date('c', $date) : $date;
    }

    /**
     * @param array $data
     * @throws LogicException
     */
    public function setData(array $data)
    {
        if (array_key_exists('data', $data)) {
            throw new LogicException("Field data is not allowed.");
        }
        if (empty($data['date'])) {
            $data['date'] = $this->extractDateFromPath($data['path']);
        }
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
    }

    /**
     * @param boolean $hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * @return string
     */
    public function setPath($path)
    {
        $this->path = $path;
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

    /**
     * @return string
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param $name
     * @throws LogicException
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        } else {
            throw new LogicException("Field {$name} does not exist.");
        }
    }

    /**
     * @param string $name
     * @return boolean
     */
    public function __isset($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } elseif (array_key_exists($name, $this->data)) {
            return $this->data[$name] !== null;
        } else {
            return false;
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->title;
    }
}
