<?php

namespace Herbie;

class Hook
{
    const ACTION = 'action';
    const FILTER = 'filter';
    const CONFIG = 'config';

    /** @var array */
    private static $hooks = [];

    /** @var array */
    private static $sorted = [];

    /**
     * Attach a hook.
     * @param string $name
     * @param callable $callback
     * @param int $priority
     * @throws \Exception
     */
    public static function attach($name, $callback, $priority = 10)
    {
        if (!isset(static::$hooks[$name][$priority])) {
            static::$hooks[$name][$priority] = array();
        }
        static::$hooks[$name][$priority][] = $callback;
    }

    /**
     * Trigger a hook action, filter or config. The return value depends on the hook type.
     * @param string $type
     * @param string $name
     * @param mixed $subject
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public static function trigger($type, $name, $subject = null, array $data = [])
    {
        if ($type == Hook::ACTION) {
            return static::triggerAction($name, $subject, $data);
        }
        if ($type == Hook::CONFIG) {
            return static::triggerConfig($name, $subject, $data);
        }
        if ($type == Hook::FILTER) {
            return static::triggerFilter($name, $subject, $data);
        }
        throw new \Exception("Given type '{$type}' doesn't exist!", 500);
    }

    /**
     * Trigger a hook action and return null.
     * @param string $name
     * @param mixed $subject
     * @param array $data
     * @return null
     * @throws \Exception
     */
    public static function triggerAction($name, $subject = null, array $data = [])
    {
        if (!static::has($name)) {
            return null;
        }
        static::sort($name);
        foreach (static::$hooks[$name] as $callbacks) {
            foreach ($callbacks as $callback) {
                $return = $callback($subject, $data, $name);
                if (!is_null($return)) {
                    throw new \Exception("The hook action '{$name}' has to return null.", 500);
                }
            }
        }
        return true;
    }

    /**
     * Trigger a hook filter and return the filtered subject.
     * @param string $name
     * @param mixed $subject
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public static function triggerFilter($name, $subject, array $data = [])
    {
        if (!static::has($name)) {
            return $subject;
        }
        static::sort($name);
        foreach (static::$hooks[$name] as $callbacks) {
            foreach ($callbacks as $callback) {
                $subject = $callback($subject, $data, $name);
                if (is_null($subject)) {
                    throw new \Exception("The hook filter '{$name}' has to return a value, null given.", 500);
                }
            }
        }
        return $subject;
    }

    /**
     * Trigger a hook config and return an composed array.
     * @param string $name
     * @param mixed $subject
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public static function triggerConfig($name, $subject, array $data = [])
    {
        $config = [];
        if (!static::has($name)) {
            return $config;
        }
        static::sort($name);
        foreach (static::$hooks[$name] as $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback)) {
                    $config[] = $callback;
                } else {
                    $return = $callback($subject, $data, $name);
                    if (is_null($return) || !is_array($return)) {
                        throw new \Exception("The hook filter '{$name}' has to return an array.", 500);
                    }
                    $config[] = $return;
                }
            }
        }
        return $config;
    }

    /**
     * Sort hook callbacks by priority and only once.
     * @param string $name
     * @return bool
     */
    private static function sort($name)
    {
        if (array_key_exists($name, static::$sorted)) {
            return false;
        }
        static::$sorted[$name] = true;
        return ksort(static::$hooks[$name], SORT_NUMERIC);
    }

    /**
     * Has a hook with the given name.
     * @param string $name
     * @return bool
     */
    public static function has($name)
    {
        return array_key_exists($name, static::$hooks);
    }

    /**
     * Get a hook with the given name.
     * @param $name
     * @return array
     */
    public static function get($name)
    {
        return array_key_exists($name, static::$hooks) ? static::$hooks[$name] : [];
    }

    /**
     * Return all hooks.
     * @return array
     */
    public static function getAll()
    {
        return static::$hooks;
    }

    /**
     * Remove the hook with the given name.
     * @param string $name
     */
    public static function remove($name)
    {
        if (array_key_exists($name, static::$hooks)) {
            unset(static::$hooks[$name]);
        }
    }

    /**
     * Remove all hooks.
     */
    public static function removeAll()
    {
        static::$hooks = [];
    }

}
