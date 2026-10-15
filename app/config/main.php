<?php

declare(strict_types=1);

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$jwtKey = require __DIR__ . '/jwt-key.php';

$config = [
    'id' => 'niushi-mall',
    'name' => $params['appName'],
    'basePath' => dirname(__DIR__),
    'charset' => 'utf-8',
    'language' => 'zh-CN',
    'bootstrap' => ['log'],
    'defaultRoute' => 'api/home/index',
    'controllerNamespace' => 'app\\modules\\api\\controllers',
    'modules' => [
        'platform' => ['class' => app\modules\platform\Module::class],
        'manage' => ['class' => app\modules\manage\Module::class],
        'api' => ['class' => app\modules\api\Module::class],
    ],
    'aliases' => [
        '@plugins' => '@app/plugins',
        '@uploads' => '@app/web/uploads',
    ],
    'components' => [
        'request' => [
            'enableCsrfValidation' => false,
            'cookieValidationKey' => $jwtKey,
            'parsers' => [
                'application/json' => yii\web\JsonParser::class,
            ],
        ],
        'response' => [
            'format' => yii\web\Response::FORMAT_JSON,
            'formatters' => [
                \yii\web\Response::FORMAT_JSON => app\common\components\ApiFormatter::class,
            ],
        ],
        'user' => [
            'identityClass' => app\common\components\AccessIdentity::class,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'errorHandler' => [
            'class' => app\common\components\ApiErrorHandler::class,
        ],
        'db' => $db,
        'cache' => YII_ENV_DEV
            ? ['class' => yii\caching\FileCache::class]
            : ['class' => yii\redis\Cache::class, 'redis' => 'redis'],
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
            'as log' => yii\queue\LogBehavior::class,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                ['class' => yii\log\FileTarget::class, 'levels' => ['error', 'warning']],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                'uploads/<path:.+>' => 'attachment/view',
            ],
        ],
        'i18n' => [
            'translations' => [],
        ],
    ],
    'params' => $params,
];

// 插件模块按需注册（见 common\plugins\PluginManager）
$config['on beforeRequest'] = [app\common\plugins\PluginManager::class, 'handleBeforeRequest'];

return $config;
