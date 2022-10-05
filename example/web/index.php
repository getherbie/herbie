<?php

if (php_sapi_name() == 'cli-server') {
    if (preg_match('/\.(?:js|css|gif|jpg|jpeg|png)$/', $_SERVER["REQUEST_URI"])) {
        return false;
    }
}

require_once(__DIR__ . '/../../vendor/autoload.php');

define('HERBIE_DEBUG', true);

use example\CustomHeader;
use example\TestFilter;
use herbie\Application;
use herbie\EventInterface;
use herbie\FilterInterface;
use herbie\HttpBasicAuthMiddleware;
use herbie\ResponseTimeMiddleware;
use herbie\TwigRenderer;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

// create a log channel
$logger = new Logger('herbie');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../site/runtime/log/logger.log', Logger::DEBUG));

// Cache
// $fileCache = new Anax\Cache\FileCache();
// $fileCache->setPath(dirname(__DIR__) . '/site/runtime/cache/page/');

$app = new Application(
    '../site',
    '../../vendor',
    $logger,
    // $fileCache
);

// Middlewares
$app->addMiddleware(ResponseTimeMiddleware::class);
$app->addMiddleware(new CustomHeader('one'));
$app->addMiddleware(new CustomHeader('two'));
$app->addMiddleware(new CustomHeader('three'));
$app->addMiddleware('blog/2015-07-30', new CustomHeader('blog'));
$app->addMiddleware('features', new CustomHeader('features'));
$app->addMiddleware('news/(.+)', new HttpBasicAuthMiddleware(['user' => 'pass']));

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
$app->addFilter('renderSegment', function (string $content, array $args, FilterInterface $chain) {
    // do something with content
    return $chain->next($content, $args, $chain);
});
$app->addFilter('renderLayout', function (string $content, array $args, FilterInterface $chain) {
    // do something with content
    return $chain->next($content, $args, $chain);
});
$app->addFilter('renderSegment', new TestFilter());


// Events
$app->addEvent('onTwigInitialized', function (EventInterface $event): void {
    /** @var TwigRenderer $twigRenderer */
    $twigRenderer = $event->getTarget();
    $twigRenderer->addFilter(new TwigFilter('my_filter', function (string $content): string {
        return $content . ' My Filter';
    }));
});

// Run
$app->run();
