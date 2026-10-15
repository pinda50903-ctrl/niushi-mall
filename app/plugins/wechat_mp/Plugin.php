<?php

declare(strict_types=1);

namespace app\plugins\wechat_mp;

/**
 * 公众号插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'wechat_mp';
    }

    public static function name(): string
    {
        return '公众号';
    }

    public static function category(): string
    {
        return 'channel';
    }

    public static function description(): string
    {
        return '微信公众号：网页授权、模板消息、菜单';
    }
}