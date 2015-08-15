<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie;

class DI implements \ArrayAccess
{
    /**
     * @var DI
     */
    private static $instance;

    /**
     * @var array
     */
    private $values = [];

    /**
     * @var array
     */
    private $frozen = [];

    /**
     * It is not allowed to call from outside
     */
    private function __construct()
    {
    }

    /**
     * Create instance on first usage
     * @return DI
     */
    public static function create()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @param string $service
     * @return mixed
     */
    public static function get($service)
    {
        return static::create()->offsetGet($service);
    }

    /**
     * @param string $name
     * @param mixed $service
     */
    public static function set($name, $service)
    {
        return static::create()->offsetSet($name, $service);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->frozen[$offset]) || isset($this->values[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (isset($this->frozen[$offset])) {
            return $this->frozen[$offset];
        }
        if (is_callable($this->values[$offset])) {
            if (!isset($this->frozen[$offset])) {
                $this->frozen[$offset] = $this->values[$offset]($this);
            }
            return $this->frozen[$offset];
        }
        if (!isset($this->frozen[$offset])) {
            $this->frozen[$offset] = $this->values[$offset];
        }
        return $this->frozen[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->values[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->frozen[$offset]);
        unset($this->values[$offset]);
    }

}
