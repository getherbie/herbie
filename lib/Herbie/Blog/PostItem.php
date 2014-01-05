<?php

namespace Herbie\Blog;

use Exception;

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
    protected $_data_ = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->setData($data);
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

        return $route;
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
     * @param string $date
     */
    public function setDate($date)
    {
        $this->date = is_numeric($date) ? date('c', $date) : $date;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        if (array_key_exists('_data_', $data)) {
            throw new \LogicException("Field _data_ is not allowed.");
        }
        foreach ($data AS $key => $value) {
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
     * @return string
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
        } elseif (array_key_exists($name, $this->_data_)) {
            return $this->_data_[$name];
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
        } elseif (array_key_exists($name, $this->_data_)) {
            return $this->_data_[$name] !== null;
        } else {
            return false;
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     * @throws \LogicException
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