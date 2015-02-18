<?php

$config = [
    'id' => 'testapp',
    'basePath' => realpath(__DIR__ . '/..'),
    'components' => [
        'realCache' => [
            'class' => 'yii\caching\FileCache',
        ],
    ],
];


if (is_file(__DIR__ . '/config.local.php')) {
    include(__DIR__ . '/config.local.php');
}

return $config;
