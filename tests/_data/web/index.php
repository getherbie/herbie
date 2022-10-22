<?php

/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

define('C3_CODECOVERAGE_ERROR_LOG_FILE', dirname(__DIR__, 3) . '/c3_error.log');
include dirname(__DIR__, 3) . '/c3.php';

use herbie\Application;
use herbie\ApplicationPaths;
use herbie\EventInterface;
use herbie\FilterInterface;
use herbie\HttpBasicAuthMiddleware;
use herbie\ResponseTimeMiddleware;
use herbie\TwigRenderer;
use tests\_data\src\CustomCommand;
use tests\_data\src\CustomHeader;
use tests\_data\src\TestFilter;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

if (php_sapi_name() == 'cli-server') {
    if (preg_match('/\.(?:js|css|gif|jpg|jpeg|png)$/', $_SERVER["REQUEST_URI"])) {
        return false;
    }
}

require_once(__DIR__ . '/../../../vendor/autoload.php');

$_ENV['HERBIE_DEBUG'] = '1';

$app = new Application(
    new ApplicationPaths(
        dirname(__DIR__),
        dirname(__DIR__) . '/site'
    )
);

$app->addCommand(CustomCommand::class);

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

$app->run();
