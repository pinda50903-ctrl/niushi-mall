<?php

declare(strict_types=1);

require __DIR__ . '/env.php';

$driver = env('DB_DRIVER', 'mysql');

if ($driver === 'sqlite') {
    $db = [
        'class' => yii\db\Connection::class,
        'dsn' => 'sqlite:@runtime/mall.db',
        'charset' => 'utf8',
        'tablePrefix' => 'nm_',
        'schemaCache' => 'cache',
    ];
} else {
    $db = [
        'class' => yii\db\Connection::class,
        'dsn' => sprintf('mysql:host=%s;port=%s;dbname=%s', env('DB_HOST', '127.0.0.1'), env('DB_PORT', '3306'), env('DB_NAME', 'niushi_mall')),
        'username' => env('DB_USER', 'root'),
        'password' => env('DB_PASS', ''),
        'charset' => 'utf8mb4',
        'tablePrefix' => 'nm_',
        'enableSchemaCache' => !YII_DEBUG,
        'schemaCacheDuration' => 3600,
        'schemaCache' => 'cache',
    ];
}

return $db;
