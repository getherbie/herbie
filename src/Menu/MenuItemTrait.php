<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu;

trait MenuItemTrait
{

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        // default / required fields
        $this->data = [
            'title' => '',
            'layout' => 'default',
            'content_type' => 'text/html',
            'authors' => [],
            'categories' => [],
            'tags' => [],
            'menu' => ''
        ];
        $this->setData($data);
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return isset($this->data['path']) ? $this->data['path'] : '';
    }

    /**
     * @param string $date
     */
    public function setDate($date)
    {
        $this->data['date'] = is_numeric($date) ? date('c', $date) : $date;
    }

    /**
     * @return string
     */
    public function getMenuTitle()
    {
        if (!empty($this->data['menu'])) {
            return $this->data['menu'];
        }
        return $this->data['title'];
    }

    /**
     * @param string $author
     * @return string
     */
    public function getAuthor($author)
    {
        $author = $this->slugify($author);
        foreach ($this->data['authors'] as $a) {
            if ($this->slugify($a) == $author) {
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
        return $this->data['authors'];
    }

    /**
     * @param string $category
     * @return string
     */
    public function getCategory($category)
    {
        $category = $this->slugify($category);
        foreach ($this->data['categories'] as $c) {
            if ($this->slugify($c) == $category) {
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
        return $this->data['categories'];
    }

    public function getTags()
    {
        return isset($this->data['tags']) ? $this->data['tags'] : [];
    }

    /**
     * @param string $tag
     * @return string
     */
    public function getTag($tag)
    {
        $tag = $this->slugify($tag);
        foreach ($this->getTags() as $t) {
            if ($this->slugify($t) == $tag) {
                return $t;
            }
        }
        return '';
    }

    /**
     * @param array $categories
     */
    public function setCategories($categories)
    {
        $this->data['categories'] = array_unique($categories);
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->data['categories'][] = $category;
    }

    /**
     * @param array $tags
     */
    public function setTags($tags)
    {
        $this->data['tags'] = array_unique($tags);
    }

    /**
     * @param string $tag
     */
    public function setTag($tag)
    {
        $this->data['tags'][] = $tag;
    }


    /**
     * @param array $authors
     */
    public function setAuthors($authors)
    {
        $this->data['authors'] = array_unique($authors);
    }

    /**
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->data['authors'][] = $author;
    }

    /**
     * @param string $author
     * @return boolean
     */
    public function hasAuthor($author)
    {
        $author = $this->slugify($author);
        foreach ($this->data['authors'] as $c) {
            if ($this->slugify($c) == $author) {
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
        $category = $this->slugify($category);
        foreach ($this->data['categories'] as $c) {
            if ($this->slugify($c) == $category) {
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
        $tag = $this->slugify($tag);
        foreach ($this->getTags() as $t) {
            if ($this->slugify($t) == $tag) {
                return true;
            }
        }
        return false;
    }

    public function getModified()
    {
        return isset($this->data['modified']) ? $this->data['modified'] : '';
    }

    /**
     * @param $name
     * @return mixed
     * @throws \LogicException
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        } else {
            throw new \LogicException("Field {$name} does not exist.");
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
        return $this->data['title'];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @param string $slug
     * @return string
     */
    protected function slugify($slug)
    {
        return strtolower($slug);
        // TODO
        return $this->herbie->getSlugGenerator()->generate($slug);
    }
}
