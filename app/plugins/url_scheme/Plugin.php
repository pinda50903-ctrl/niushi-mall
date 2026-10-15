<?php

declare(strict_types=1);

namespace app\plugins\url_scheme;

/**
 * 小程序链接插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'url_scheme';
    }

    public static function name(): string
    {
        return '小程序链接';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '生成微信小程序 URL Scheme/Short Link';
    }
}