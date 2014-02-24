<?php

return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => 'Yii Herbie Connector',
    'import' => array(
        'application.components.*',
        'vendor.getherbie.yii-module.components.*'
    ),
    'modules' => array(
        'herbie' => array(
            'class' => 'vendor.getherbie.yii-module.HerbieModule',
            'sitePath' => __DIR__ . '/../../_site'
        ),
    ),
    'components' => array(
        'urlManager' => array(
            'class' => 'vendor.getherbie.yii-module.components.UrlManager',
            'urlFormat' => 'path',
            'showScriptName' => false,
            'rules' => array(
            )
        ),
        'errorHandler' => array(
            'errorAction' => '/site/error',
        ),
    )
);