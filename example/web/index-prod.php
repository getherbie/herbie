<?php

declare(strict_types=1);

require_once(dirname(__DIR__) . '/vendor/autoload.php');

use herbie\Application;
use herbie\ApplicationPaths;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$appPath = dirname(__DIR__);

// create application paths
$appPaths = new ApplicationPaths($appPath);

// create a logger channel
$logger = new Logger('herbie');
$logger->pushHandler(new StreamHandler($appPath . '/site/runtime/log/logger.log', Logger::WARNING));

// run application
(new Application($appPaths, $logger))->run();
