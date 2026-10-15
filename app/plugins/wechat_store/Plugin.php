<?php

declare(strict_types=1);

namespace app\plugins\wechat_store;

/**
 * 微信门店插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'wechat_store';
    }

    public static function name(): string
    {
        return '微信门店';
    }

    public static function category(): string
    {
        return 'store';
    }

    public static function description(): string
    {
        return '同步微信门店接口的门店信息';
    }
}