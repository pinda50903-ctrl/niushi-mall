<?php

declare(strict_types=1);

namespace app\plugins\store_helper;

/**
 * 店员助手插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'store_helper';
    }

    public static function name(): string
    {
        return '店员助手';
    }

    public static function category(): string
    {
        return 'store';
    }

    public static function description(): string
    {
        return '店员移动端：订单处理、核销、库存盘点';
    }
}