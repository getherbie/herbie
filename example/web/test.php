<?php

use Herbie\ZendConfig;

require_once(__DIR__ . '/../../vendor/autoload.php');

$APP_PATH = '';
$SITE_PATH = '';
$WEB_PATH = '';
$WEB_URL = '';
$data = require '../../config/test.php';
$config = new ZendConfig($data);

print_r($config->fileExtensions->toArray());
