<?php

declare(strict_types=1);

namespace app\plugins\alipay_app;

/**
 * 支付宝小程序插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'alipay_app';
    }

    public static function name(): string
    {
        return '支付宝小程序';
    }

    public static function category(): string
    {
        return 'channel';
    }

    public static function description(): string
    {
        return '支付宝小程序端登录与支付适配';
    }
}