<?php
/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (php_sapi_name() == 'cli-server') {
    if (preg_match('/\.(?:js|css|gif|jpg|jpeg|png)$/', $_SERVER["REQUEST_URI"])) {
        return false;
    }
}

require_once(__DIR__ . '/../../../vendor/autoload.php');

define('HERBIE_DEBUG', true);

use example\CustomHeader;
use example\TestFilter;
use herbie\Application;
use herbie\HttpBasicAuthMiddleware;
use herbie\ResponseTimeMiddleware;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

$app = new Application(
    '../site',
    '../../../vendor',
);

$app->run();
