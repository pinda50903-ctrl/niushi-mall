<?php

/**
 * 前端静态站点开发服务器路由脚本（无 no-cache 版由 PHP 内置服务器默认行为处理）
 *
 * php -S 127.0.0.1:8092 -t frontend-uniapp dev-static.php
 *
 * 前端为纯静态 SPA，这里仅做两件事：
 * 1) 真实文件直接输出；
 * 2) 其余回退到 index.html（hash 路由本身不需要，但直接访问根路径更稳）。
 */

declare(strict_types=1);

$root = __DIR__ . '/frontend-uniapp';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$target = realpath($root . $path);

header('Cache-Control: no-store, must-revalidate');

if ($target !== false && is_file($target) && str_starts_with($target, realpath($root) . DIRECTORY_SEPARATOR)) {
    return false;
}

readfile($root . '/index.html');
return true;
