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

class PathHelper
{
    /**
     * @param string $path
     * @return string
     */
    public static function extractDateFromPath($path)
    {
        $filename = basename($path);
        preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}).*$/', $filename, $matches);
        return $matches[1];
    }
}
