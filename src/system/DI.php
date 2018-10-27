<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <https://www.tebe.ch>
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

    final private function __construct() {}
    final private function __clone() {}

    /**
     * @return DI
     */
    public static function instance()
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
        return static::instance()->offsetGet($service);
    }

    /**
     * @param string $service
     * @return bool
     */
    public static function has($service)
    {
        return static::instance()->offsetExists($service);
    }

    /**
     * @param string $name
     * @param mixed $service
     */
    public static function set($name, $service)
    {
        static::instance()->offsetUnset($name);
        static::instance()->offsetSet($name, $service);
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
     * @throws \Exception
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            $message = sprintf("The object '%s' is not stored in DI container.", $offset);
            throw new \Exception($message, 500);
        }
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
