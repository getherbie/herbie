<?php

/**
 * This file is part of Herbie.
 *
 * (c) Thomas Breuss <www.tebe.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Herbie\Application;

if (php_sapi_name() == 'cli-server') {
    if (preg_match('/\.(?:js|css|gif|jpg|jpeg|png)$/', $_SERVER["REQUEST_URI"])) {
        return false;
    }
}

require_once(dirname(__DIR__, 2) . '/vendor/autoload.php');

define('HERBIE_DEBUG', true);

$app = new Application(
    dirname(__DIR__) . '/site/',
    dirname(__DIR__, 2) . '/vendor/'
);

$app->run();
