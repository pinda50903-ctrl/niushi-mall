<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'niushi-mall-console',
    'name' => $params['appName'] . '控制台',
    'basePath' => dirname(__DIR__),
    'charset' => 'utf-8',
    'language' => 'zh-CN',
    'bootstrap' => ['log', 'queue'],
    'controllerNamespace' => 'app\\console\\controllers',
    'controllerMap' => [
        'migrate' => [
            'class' => app\console\controllers\MigrateController::class,
            'migrationPath' => null,
            'migrationNamespaces' => [
                'app\\console\\migrations',
                'app\\plugins\\*\\migrations',
            ],
        ],
        'queue' => yii\queue\db\Command::class,
    ],
    'components' => [
        'db' => $db,
        'cache' => ['class' => yii\caching\FileCache::class],
        'redis' => [
            'class' => yii\redis\Connection::class,
            'hostname' => env('REDIS_HOST', '127.0.0.1'),
            'port' => (int)env('REDIS_PORT', 6379),
            'database' => (int)env('REDIS_DB', 0),
            'password' => env('REDIS_PASS'),
        ],
        'queue' => [
            'class' => yii\queue\db\Queue::class,
            'db' => 'db',
            'tableName' => '{{%queue_job}}',
            'channel' => 'mall',
            'mutex' => yii\mutex\FileMutex::class,
        ],
        'log' => [
            'targets' => [
                ['class' => yii\log\FileTarget::class, 'levels' => ['error', 'warning']],
            ],
        ],
    ],
    'params' => $params,
];

return $config;
