<?php

declare(strict_types=1);

namespace app\plugins\gift;

/**
 * 赠品插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'gift';
    }

    public static function name(): string
    {
        return '赠品';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '满额赠品/买赠活动管理';
    }
}