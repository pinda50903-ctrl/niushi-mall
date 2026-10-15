<?php

declare(strict_types=1);

namespace app\plugins\scrm;

/**
 * 客户管理插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'scrm';
    }

    public static function name(): string
    {
        return '客户管理';
    }

    public static function category(): string
    {
        return 'member';
    }

    public static function description(): string
    {
        return '用户标签、分组与精细化运营';
    }
}