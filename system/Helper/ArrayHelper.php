<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Herbie\Helper;

class ArrayHelper
{

    public static function filterEmptyElements(Array $array)
    {
        $callback = function ($value) {
            // @see http://php.net/manual/en/function.empty.php
            return !empty($value);
        };
        return array_filter($array, $callback);
    }

    public static function sortArrayByArray(Array $array, Array $orderArray)
    {
        $ordered = [];
        foreach ($orderArray as $key) {
            if (array_key_exists($key, $array)) {
                $ordered[$key] = $array[$key];
                unset($array[$key]);
            }
        }
        return $ordered + $array;
    }

    public static function appendToItems(Array $array, $append)
    {
        $closure = function($value) use ($append) { return $value . $append; };
        return array_map($closure, $array);
    }
}
