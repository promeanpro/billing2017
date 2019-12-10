<?php

$yaml = require __DIR__ . '/loader.php';

Yii::setAlias('@restcomponents', dirname(__DIR__) . '/vendor/charlesportwoodii/yii2-api-rest-components');

$config = [
    'id' => $yaml['app']['id'],
    'name' => $yaml['app']['name'],
    'basePath' => dirname(__DIR__),
    'bootstrap' => [ 'log' ],
    'language' => 'en-US',
    'sourceLanguage' => 'en-US',
    'components' => [
        // Работа с очередями
        'messageQueue' => [
            'class' => '\app\components\messageBrokers\RabbitMQ',
            'host' => '127.0.0.1',
            'port' => '5672',
            'timeout' => 15
        ],
        'rpcRequest' => [
            'class' => '\app\components\RPCRequest',
        ],
        'request' => [
            'enableCookieValidation'    => false,
            'enableCsrfValidation'      => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'application/json+25519' => 'yrc\web\Json25519Parser'
            ]
        ],
        'response' => [
            'class'      => 'yii\web\Response',
            'format'     => yii\web\Response::FORMAT_JSON,
            'charset'    => 'UTF-8',
//            'formatters' => [
//                \yrc\web\Response::FORMAT_JSON25519 => [
//                    'class'         => 'yrc\web\Json25519ResponseFormatter',
//                    'prettyPrint'   => YII_DEBUG,
//                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
//                ],
//                \yrc\web\Response::FORMAT_JSON => [
//                    'class'         => 'yrc\web\JsonResponseFormatter',
//                    'prettyPrint'   => YII_DEBUG,
//                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
//                ]
//            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'class'                 => 'yii\web\UrlManager',
            'showScriptName'        => false,
            'enableStrictParsing'   => true,
            'enablePrettyUrl'       => true,
            'rules' => [
                [
                    'pattern'   => '/api/v1/<controller>/<action>',
                    'route'     => 'api/v1/<controller>/<action>',
                ],

            ]
        ],
        'user' => [
            'class' => 'yii\web\User',
            'identityClass' => $yaml['yii2']['user'],
            'enableSession' => false
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'categories' => [
                        'except' => [
                            'yii\web\HttpException:400',
                            'yii\web\HttpException:401',
                            'yii\web\HttpException:404'
                        ],
                    ],
                ]
            ]
        ],
        'db' => require(__DIR__ . '/db.php')
    ],
    'params' => require(__DIR__ . '/params.php')
];

return $config;