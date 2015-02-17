<?php

$config = [
    'id' => 'testapp',
    'basePath' => realpath(__DIR__ . '/..'),
    'components' => [
        'urlManager' => [
            'class' => '\common\components\url\UrlManager',
            'showDefault' => false,
            'enablePrettyUrl' => true,
            'languages' => ['ru', 'ua' => 'uk'],
            'showScriptName' => false,
            'enableStrictParsing' => true,
            'rules' => [
                '' => 'site/index',
            ],
        ],
    ],
];


if (is_file(__DIR__ . '/config.local.php')) {
    include(__DIR__ . '/config.local.php');
}

return $config;
