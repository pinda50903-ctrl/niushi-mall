<?php

declare(strict_types=1);

namespace app\plugins\sandbox;

/**
 * 沙箱插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'sandbox';
    }

    public static function name(): string
    {
        return '沙箱';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '联调沙箱：模拟支付、模拟回调、测试数据';
    }
}