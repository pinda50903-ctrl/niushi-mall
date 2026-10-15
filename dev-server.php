<?php

/**
 * 本地开发用 PHP 内置服务器路由脚本
 *
 * php -S 127.0.0.1:8090 -t app/web dev-server.php
 *
 * 1) 内置服务器默认把所有请求交给入口脚本，导致 web/uploads 下的静态图片
 *    走 Yii 路由而返回 JSON。这里先判定真实文件，静态资源直接输出。
 * 2) 静态资源统一发 no-cache，避免联调时浏览器缓存旧模块导致改动不生效。
 */

declare(strict_types=1);

$webRoot = __DIR__ . '/app/web';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$target = realpath($webRoot . $path);

// 防目录穿越：仅允许 web 目录内的真实文件
if ($target !== false && is_file($target) && str_starts_with($target, realpath($webRoot) . DIRECTORY_SEPARATOR)) {
    if (strtolower(pathinfo($target, PATHINFO_EXTENSION)) !== 'php') {
        header('Cache-Control: no-store, must-revalidate');
        return false;
    }
}

require $webRoot . '/index.php';
return true;
