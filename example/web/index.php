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

require_once(__DIR__ . '/../../vendor/autoload.php');

define('HERBIE_DEBUG', 1);

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

$app = new Application('../site', '../../vendor');

// create a log channel
$logger = new Logger('herbie');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../site/runtime/log/logger.log', Logger::DEBUG));
$app->setLogger($logger);

// Cache
// $fileCache = new Anax\Cache\FileCache();
// $fileCache->setPath(dirname(__DIR__) . '/site/runtime/cache/page/');
// $app->setPageCache($fileCache);

// Middlewares
$app->addMiddleware(new ResponseTimeMiddleware());
$app->addMiddleware(new CustomHeader('one'));
$app->addMiddleware(new CustomHeader('two'));
$app->addMiddleware(new CustomHeader('three'));
$app->addMiddleware('blog/2015-07-30', new CustomHeader('blog'));
$app->addMiddleware('features', new CustomHeader('features'));
$app->addMiddleware('news/january', new HttpBasicAuthMiddleware(['user' => 'pass']));

// Twig
$app->addTwigFunction(new TwigFunction('myfunction', function () {
    return 'My Function';
}));
$app->addTwigFilter(new TwigFilter('myfilter', function () {
    return 'My Filter';
}));
$app->addTwigTest(new TwigTest('mytest', function () {
    return true;
}));

// Filters
$app->attachFilter('renderSegment', function (string $content, array $args, $chain) {
    // do something with content
    return $chain->next($content, $args, $chain);
});
$app->attachFilter('renderLayout', function (string $content, array $args, $chain) {
    // do something with content
    return $chain->next($content, $args, $chain);
});
$app->attachFilter('renderSegment', new TestFilter());

// Run
$app->run();
