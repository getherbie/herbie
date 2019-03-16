<?php

/*
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\TwigTest;

return new TwigTest('odd', function ($value) {
    return ($value % 2) != 0;
});
