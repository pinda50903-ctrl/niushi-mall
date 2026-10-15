<?php

declare(strict_types=1);

namespace app\plugins\h5;

/**
 * 手机网站插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'h5';
    }

    public static function name(): string
    {
        return '手机网站';
    }

    public static function category(): string
    {
        return 'channel';
    }

    public static function description(): string
    {
        return 'H5 端登录授权与支付适配';
    }
}