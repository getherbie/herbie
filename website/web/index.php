<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

herbie\handle_internal_webserver_assets(__FILE__);

use herbie\Application;
use herbie\ApplicationPaths;
use herbie\FileInfo;
use herbie\FileInfoSortableIterator;
use herbie\finder\CustomFilterIterator;
use herbie\finder\DepthRangeFilterIterator;
use herbie\finder\FileTypeFilterIterator;
use herbie\RecursiveDirectoryIterator;

// create iterator
$dir = dirname(__DIR__) . '/site/pages';
$flags = RecursiveDirectoryIterator::SKIP_DOTS;

$iterator = new RecursiveDirectoryIterator($dir, $flags);
$iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);

// filtering
$iterator = new DepthRangeFilterIterator($iterator, 0, 3);
$iterator = new FileTypeFilterIterator($iterator, 0);
$iterator = new CustomFilterIterator($iterator, [function (FileInfo $file) {
    if (strlen($file->getFilename()) > 12) {
        return false;
    }
}]);

// sorting
$iterator = (new FileInfoSortableIterator($iterator, FileInfoSortableIterator::SORT_BY_NAME))->getIterator();

/** @var herbie\FileInfo $fileInfo */
foreach ($iterator as $fileInfo) {
    echo $fileInfo->getRelativePathname() . '<br>';
}
exit;


// create app paths
$appPaths = (new ApplicationPaths(dirname(__DIR__, 2)))
    ->setSite(dirname(__DIR__) . '/site')
    ->setWeb(__DIR__);

$app = new Application($appPaths);

$app->run();
