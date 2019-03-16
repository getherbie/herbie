<?php

/*
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\TwigFilter;

return new TwigFilter('reverse', function ($string) {
    return strrev($string);
});
