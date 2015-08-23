<?php

namespace Herbie;

class Hook
{
    const ACTION = 'action';
    const FILTER = 'filter';

    /** @var array  */
    private static $hooks = array();

    /**
     * Attach a hook.
     * @param string $name
     * @param callable $callback
     * @throws \Exception
     */
    public static function attach($name, $callback)
    {
        $args = func_get_args();
        $numArgs = func_num_args();

        if ($numArgs > 3) {
            throw new \Exception("You can't call Hook::attach() with more than 3 arguments!", 500);
        }

        if (!isset(static::$hooks[$name])) {
            static::$hooks[$name] = array();
        }

        if ($numArgs == 2) {
            static::$hooks[$name][] = $callback;
        } else {
            static::$hooks[$name][$callback] = $args[2];
        }

    }

    /**
     * Trigger a hook action or filter.
     * @param string $type
     * @param string $name
     * @param mixed $subject
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public static function trigger($type, $name, $subject = null, array $data = [])
    {
        if ($type == Hook::ACTION) {
            return static::triggerAction($name, $subject, $data);
        }
        if ($type == Hook::FILTER) {
            return static::triggerFilter($name, $subject, $data);
        }
        throw new \Exception("Given type '{$type}' doesn't exist!", 500);
    }

    /**
     * Trigger a hook action and return a boolean.
     * @param string $name
     * @param mixed $subject
     * @param array $data
     * @return bool
     */
    public static function triggerAction($name, $subject = null, array $data = [])
    {
        if (!array_key_exists($name, static::$hooks)) {
            return false;
        }
        foreach (static::$hooks[$name] as $callback) {
            if (is_callable($callback)) {
                $callback($subject, $data, $name);
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
        if (!array_key_exists($name, static::$hooks)) {
            return $subject;
        }
        foreach (static::$hooks[$name] as $callback) {
            if (is_callable($callback)) {
                $subject = $callback($subject, $data, $name);
                if (is_null($subject)) {
                    throw new \Exception("The hook filter '{$name}' has to return a value instead of null.", 404);
                }
            }
        }
        return $subject;
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
