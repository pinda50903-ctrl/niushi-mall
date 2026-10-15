<?php

declare(strict_types=1);

namespace app\plugins\baidu_app;

/**
 * 百度小程序插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'baidu_app';
    }

    public static function name(): string
    {
        return '百度小程序';
    }

    public static function category(): string
    {
        return 'channel';
    }

    public static function description(): string
    {
        return '百度智能小程序端登录与支付适配';
    }
}