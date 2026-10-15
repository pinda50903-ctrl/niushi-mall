<?php

declare(strict_types=1);

require __DIR__ . '/env.php';

return [
    'appName' => '牛市商城',
    'appVersion' => '1.0.0',
    'cdnUrl' => env('CDN_URL', ''),
    'adminUrl' => env('ADMIN_URL', 'http://localhost:5173'),
    'h5Url' => env('H5_URL', 'http://localhost:8080'),
    'upload' => [
        'maxSize' => 10 * 1024 * 1024,
        'imageExts' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'videoExts' => ['mp4', 'mov'],
        'fileExts' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip'],
    ],
    'order' => [
        'closeMinutes' => 30,
        'autoFinishDays' => 7,
    ],
    'plugins' => [],
];
