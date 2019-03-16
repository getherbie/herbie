<?php

/*
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\TwigFunction;

return new TwigFunction('hello', function ($name) {
    return "Hello {$name}!";
});
