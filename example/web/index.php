<?php

require_once(__DIR__ . '/../../vendor/autoload.php');

herbie\handle_internal_webserver_assets(__FILE__);

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
    dirname(__DIR__, 2),
    dirname(__DIR__) . '/site',
    $logger,
    // $fileCache
);

// App Middlewares
$app->addAppMiddleware(ResponseTimeMiddleware::class);
$app->addAppMiddleware(new CustomHeader('one'));
$app->addAppMiddleware(new CustomHeader('two'));
$app->addAppMiddleware(new CustomHeader('three'));

// Route Middlewares
$app->addRouteMiddleware('blog/2015-07-30', new CustomHeader('blog'));
$app->addRouteMiddleware('features', new CustomHeader('features'));
$app->addRouteMiddleware('news/(.+)', new HttpBasicAuthMiddleware(['user' => 'pass']));

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
