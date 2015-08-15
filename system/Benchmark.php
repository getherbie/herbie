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

class Benchmark
{
    /**
     * @var int|null
     */
    static $start = null;

    /**
     * @param bool $reset
     * @return string (number format)
     */
    public static function mark($reset = false)
    {
        if ($reset) {
            self::$start = null;
        }
        if (self::$start === null) {
            self::$start = microtime(true);
            return number_format(0, 4);
        }
        return number_format(microtime(true) - self::$start, 4);
    }

}
