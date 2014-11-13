<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Menu\Page;

use Herbie\Menu\ItemInterface;

class Item implements ItemInterface
{
    /**
     * @var string
     */
    protected $route;

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
     * @var int
     */
    protected $depth;

    /**
     * @var string
     */
    protected $hidden;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->setData($data);
    }

    /**
     * @param array $data
     * @throws \LogicException
     */
    public function setData(array $data)
    {
        if (array_key_exists('data', $data)) {
            throw new \LogicException("Field data is not allowed.");
        }
        foreach ($data as $key => $value) {
            $this->__set($key, $value);
        }
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
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function getParentRoute()
    {
        return trim(dirname($this->route), '.');
    }

    /**
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @return boolean
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return boolean
     */
    public function getVisible()
    {
        return false === $this->getHidden();
    }

    /**
     * @return string
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return bool
     */
    public function isStartPage()
    {
        return trim($this->route) == '';
    }

    /**
     * @param int|string $date
     */
    public function setDate($date)
    {
        $this->date = is_numeric($date) ? date('c', $date) : $date;
    }

    /**
     * @param boolean $hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * @param string $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * @param int $depth
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param $name
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
        return $this->title;
    }

    /**
     * @param string $route
     * @return bool
     */
    public function routeEquals($route)
    {
        return $this->route == $route;
    }

    /**
     * @param string $route
     * @return bool
     */
    public function routeInRootPath($route)
    {
        if (empty($route) || empty($this->route)) {
            return false;
        }
        return 0 === strpos($route, $this->route);
    }
}
