<?php

declare(strict_types=1);

require_once(dirname(__DIR__) . '/vendor/autoload.php');

use herbie\Application;
use herbie\ApplicationPaths;

$appPath = dirname(__DIR__);

// create application paths
$appPaths = new ApplicationPaths($appPath);

// run application
(new Application($appPaths))->run();
