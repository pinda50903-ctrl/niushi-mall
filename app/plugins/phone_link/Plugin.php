<?php

declare(strict_types=1);

namespace app\plugins\phone_link;

/**
 * 电话跳转插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'phone_link';
    }

    public static function name(): string
    {
        return '电话跳转';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '一键拨号组件与电话按钮配置';
    }
}