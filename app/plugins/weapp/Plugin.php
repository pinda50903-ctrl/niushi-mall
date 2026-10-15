<?php

declare(strict_types=1);

namespace app\plugins\weapp;

/**
 * 微信小程序插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'weapp';
    }

    public static function name(): string
    {
        return '微信小程序';
    }

    public static function category(): string
    {
        return 'channel';
    }

    public static function description(): string
    {
        return '微信小程序端：登录、支付、订阅消息';
    }
}