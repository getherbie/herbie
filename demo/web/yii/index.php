<?php

ini_set('display_errors', 1);

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../../../yii/1.1.14/framework/yii.php');

$config = __DIR__ . '/protected/config/main.php';
Yii::setPathOfAlias('vendor', __DIR__ . '/../../vendor/');

// create a Web application instance and run
Yii::createWebApplication($config)->run();
