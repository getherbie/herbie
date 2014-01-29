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

use Herbie\Loader\PageLoader;
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
    protected $type;

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
     * @param bool $trimExtension
     * @return string
     */
    public function getLayout($trimExtension=false)
    {
        if($trimExtension) {
            return preg_replace("/\\.[^.\\s]{3,4}$/", "", $this->layout);
        }
        return $this->layout;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
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
        if(array_key_exists($id, $this->segments)) {
            return $this->segments[$id];
        }
        return null;
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param PageLoader $loader
     */
    public function load(PageLoader $loader)
    {
        $data = $loader->load();
        $this->type = $loader->getExtension();
        if(array_key_exists('data', $data)) {
            $this->setData($data['data']);
        }
        if(array_key_exists('segments', $data)) {
            $this->setSegments($data['segments']);
        }
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
        foreach($data AS $key=>$value) {
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
     * @param bool $preserveExtension
     */
    public function setPreserveExtension($preserveExtension)
    {
        $this->preserveExtension = (bool)$preserveExtension;
    }

    /**
     * @param string $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * @param array $segments
     */
    public function setSegments(array $segments = [])
    {
        $this->segments = $segments;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $members = [
            'layout' => $this->layout,
            'type' => $this->type,
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