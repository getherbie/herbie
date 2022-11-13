<?php

declare(strict_types=1);

require_once(dirname(__DIR__, 2) . '/vendor/autoload.php');

herbie\handle_internal_webserver_assets(__FILE__);

use herbie\Application;
use herbie\ApplicationPaths;

// create app paths
$appPaths = (new ApplicationPaths(dirname(__DIR__, 2)))
    ->setSite(dirname(__DIR__) . '/site')
    ->setWeb(__DIR__);

$app = new Application($appPaths);

$app->run();
