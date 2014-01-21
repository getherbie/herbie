<?php

/*
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if(!isset($this) || (!$this instanceof HerbieExtension)) {
}

return new \Twig_SimpleFunction('test2', function() {
    return 'TEST2 for external function';
}, ['is_safe' => ['html']]);
