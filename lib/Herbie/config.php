<?php

if (is_null($this) || ('Herbie\Application' != get_class($this))) {
    die('Not allowed to access this file.');
}

return array(
    'app' => array(
        'path' => $this['appPath']
    ),
    'site' => array(
        'path' => $this['sitePath']
    ),
    'layouts' => array(
        'path' => $this['sitePath'] . '/layouts'
    ),
    'pages' => array(
        'path' => $this['sitePath'] . '/pages'
    ),
    'posts' => array(
        'path' => $this['sitePath'] . '/posts'
    ),
    'data' => array(
        'path' => $this['sitePath'] . '/data',
        'extensions' => array('yml', 'yaml')
    ),
    'nice_urls' => true,
    'twig' => array(
        'debug' => false,
        'cache' => $this['sitePath'] . '/cache/twig',
        'extend' => array(
            'functions' => $this['sitePath'] . '/plugins/twig/functions',
            'filters' => $this['sitePath'] . '/plugins/twig/filters',
            'tests' => $this['sitePath'] . '/plugins/twig/tests',
        )
    ),
    'cache' => array(
        'page' => array(
            'enable' => false,
            'dir' => $this['sitePath'] . '/cache/page',
            'expire' => 18000
        ),
        'data' => array(
            'enable' => false,
            'dir' => $this['sitePath'] . '/cache/data',
            'expire' => 18000
        )
    )
);