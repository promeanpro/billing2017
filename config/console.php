<?php

$yaml = require __DIR__ . '/loader.php';

Yii::setAlias('@tests', dirname(__DIR__) . '/tests');
Yii::setAlias('@restcomponents', dirname(__DIR__) . '/vendor/charlesportwoodii/yii2-api-rest-components');

$config = [
    'id' => $yaml['app']['id'] . '-console',
    'name' => $yaml['app']['name'] . '-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'enableCoreCommands' => YII_DEBUG,
    'controllerNamespace' => 'app\commands',
    'components' => [
        'messageQueue' => [
            'class' => '\app\components\messageBrokers\RabbitMQ',
            'host' => '127.0.0.1',
            'port' => '5672',
            'timeout' => 15
        ],
        // Менеджер управления демонами
        'daemonManager' => [
            'class' => '\app\components\daemons\DaemonManager',
            // Путь к каталогу, где менеджер демонов хранит свои файлы
            'dataPath' => '@runtime/daemons',
            // Список используемых демонов
            'daemons' => [
                // Демон для обработки задач из очередей
                //при изменении этих настроек следует делать php yii daemons/restart
                //для примера сделаем одного демона для пополения счета
                'EventDaemon' => [
                    'class' => '\app\components\daemons\EventDaemon',
                    'count' => 4, //количество процессов
                    'tasks' => [
                        //формат:
                        //тип события(регулируется через models/transactions/modelName->getCommandName- команда в yii2
                        'AddBalance' => 'events',
                        'SubstractBalance' => 'events',
                        'TransferBalance' => 'events',
                        'Approve' => 'events',
                        'Decline' => 'events',
                        'Reserve' => 'events'
                    ]
                ],
            ]
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'logFile' => '@app/runtime/logs/trace.log',
                    'levels' => ['trace'],
                    'logVars' => [],
                    'categories' => ['eventHandler'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
    ],
    'params' => require(__DIR__ . '/params.php')
];


return $config;