<?php

require_once(dirname(__DIR__, 2) . '/vendor/autoload.php');

herbie\handle_internal_webserver_assets(__FILE__);

use herbie\Application;
use herbie\ApplicationPaths;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// create app paths
$appPaths = new ApplicationPaths(
    dirname(__DIR__, 2),
    dirname(__DIR__) . '/site',
);

// create a log channel
$logger = new Logger('herbie');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../site/runtime/log/logger.log', Logger::DEBUG));

// Cache
// $fileCache = new Anax\Cache\FileCache();
// $fileCache->setPath(dirname(__DIR__) . '/site/runtime/cache/page/');

$app = new Application(
    $appPaths,
    $logger,
    // $fileCache
);

$app->run();
