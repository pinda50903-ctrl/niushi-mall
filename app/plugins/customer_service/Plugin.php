<?php

declare(strict_types=1);

namespace app\plugins\customer_service;

/**
 * 客服插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'customer_service';
    }

    public static function name(): string
    {
        return '客服';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '客服会话入口与常见问题配置';
    }
}