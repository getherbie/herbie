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

    public static function filterEmptyElements($array)
    {
        $callback = function($value) {
            // @see http://php.net/manual/en/function.empty.php
            return !empty($value);
        };
        return array_filter($array, $callback);
    }

}
