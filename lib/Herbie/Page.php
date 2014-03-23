<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

use LogicException;

/**
 * Stores the page.
 */
class Page
{
    /**
     * @var string
     */
    protected $layout;

    /**
     * @var string
     */
    protected $format;

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
    protected $preserveExtension;

    /**
     * @var string
     */
    protected $contentType;

    /**
     * @var array
     */
    protected $authors = [];

    /**
     * @var array
     */
    protected $categories = [];

    /**
     * @var array
     */
    protected $tags = [];

    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $_data_ = [];

    /**
     * @var array
     */
    protected $segments = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->layout = 'default.html';
    }

    /**
     * @return array
     */
    public function getAuthors()
    {
        return $this->authors;
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
    public function getContentType()
    {
        if (empty($this->contentType)) {
            return 'text/html';
        }
        return $this->contentType;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param bool $trimExtension
     * @return string
     */
    public function getLayout($trimExtension = false)
    {
        if ($trimExtension) {
            return preg_replace("/\\.[^.\\s]{3,4}$/", "", $this->layout);
        }
        return $this->layout;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return bool
     */
    public function getPreserveExtension()
    {
        return $this->preserveExtension;
    }

    /**
     * @return array
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     *
     * @param string $id
     * @return null|string
     */
    public function getSegment($id)
    {
        if (array_key_exists($id, $this->segments)) {
            return $this->segments[$id];
        }
        return null;
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
     * @param array $authors
     */
    public function setAuthors($authors)
    {
        $this->authors = (array) $authors;
    }

    /**
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->authors[] = $author;
    }

    /**
     * @param array $categories
     */
    public function setCategories($categories)
    {
        $this->categories = (array) $categories;
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->categories[] = $category;
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * @param array $data
     * @throws LogicException
     */
    public function setData(array $data)
    {
        if (array_key_exists('segments', $data)) {
            throw new LogicException("Field segments is not allowed.");
        }
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
    }

    /**
     * @param string|int $date
     */
    public function setDate($date)
    {
        $this->date = is_numeric($date) ? date('c', $date) : $date;
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        switch ($format) {
            case 'md':
            case 'markdown':
                $format = 'markdown';
                break;
            case 'textile':
                $format = 'textile';
                break;
            default:
                $format = 'raw';
        }
        $this->format = $format;
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @param bool $preserveExtension
     */
    public function setPreserveExtension($preserveExtension)
    {
        $this->preserveExtension = (bool) $preserveExtension;
    }

    /**
     * @param array $segments
     */
    public function setSegments(array $segments = [])
    {
        $this->segments = $segments;
    }

    /**
     * @param string $tag
     */
    public function setTag($tag)
    {
        $this->tags[] = $tag;
    }

    /**
     * @param array $tags
     */
    public function setTags($tags)
    {
        $this->tags = (array) $tags;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $members = [
            'layout' => $this->layout,
            'format' => $this->format,
            'title' => $this->title,
            'date' => $this->date
        ];
        return array_merge($members, $this->_data_);
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
        } elseif (array_key_exists($name, $this->_data_)) {
            return $this->_data_[$name];
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
        } elseif (array_key_exists($name, $this->_data_)) {
            return $this->_data_[$name] !== null;
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
            $this->_data_[$name] = $value;
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
