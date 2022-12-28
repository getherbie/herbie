<?php

/**
 * This file is part of Herbie.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

define('C3_CODECOVERAGE_ERROR_LOG_FILE', dirname(__DIR__, 3) . '/c3_error.log');
include dirname(__DIR__, 3) . '/c3.php';

use herbie\Application;
use herbie\ApplicationPaths;
use herbie\events\RenderLayoutEvent;
use herbie\events\RenderSegmentEvent;
use herbie\events\TwigInitializedEvent;
use herbie\HttpBasicAuthMiddleware;
use herbie\ResponseTimeMiddleware;
use herbie\tests\_data\src\CustomCommand;
use herbie\tests\_data\src\CustomHeader;
use herbie\tests\_data\src\TestFilter;
use Twig\TwigFilter;

if (php_sapi_name() === 'cli-server') {
    if (preg_match('/\.(?:js|css|gif|jpg|jpeg|png)$/', $_SERVER["REQUEST_URI"])) {
        return false;
    }
}

require_once __DIR__ . '/../../../vendor/autoload.php';

$_ENV['HERBIE_DEBUG'] = '1';

$app = new Application(
    new ApplicationPaths(
        dirname(__DIR__),
        dirname(__DIR__) . '/site'
    )
);

$app->addConsoleCommand(CustomCommand::class);

$app->addApplicationMiddleware(ResponseTimeMiddleware::class);

$app->addApplicationMiddleware(new CustomHeader('one'));

$app->addApplicationMiddleware(new CustomHeader('two'));

$app->addApplicationMiddleware(new CustomHeader('three'));

$app->addRouteMiddleware('blog/2015-07-30', new CustomHeader('blog'));

$app->addRouteMiddleware('features', new CustomHeader('features'));

$app->addRouteMiddleware('news/(.+)', new HttpBasicAuthMiddleware(['user' => 'pass']));

$app->addTwigFunction('myfunction', function () {
    return 'My Function';
});

$app->addTwigFilter('myfilter', function () {
    return 'My Filter';
});

$app->addTwigTest('mytest', function () {
    return true;
});

$app->addEventListener(RenderSegmentEvent::class, function (RenderSegmentEvent $event) {
    // do something with $event
});

$app->addEventListener(RenderLayoutEvent::class, function (RenderLayoutEvent $event) {
    // do something with $event
});

$app->addEventListener(RenderSegmentEvent::class, new TestFilter());

$app->addEventListener(TwigInitializedEvent::class, function (TwigInitializedEvent $event): void {
    $event->getEnvironment()->addFilter(
        new TwigFilter('my_filter', function (string $content): string {
            return $content . ' My Filter';
        })
    );
});

$app->run();
