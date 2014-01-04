<?php

ini_set('display_errors', 1);

require_once(__DIR__ . '/../vendor/autoload.php');

$app = new Herbie\Application('../site');
$app->run();


function debug($what)
{
    echo "<pre>";
    print_r($what);
    echo "</pre>";
}

/*
$config = __DIR__ . '/../herbie/config/default.yml';
$app = Herbie\Application::create($config);
$app->run();

Herbie\debug($app->getConfig()->nice_urls);
*/