<?php

declare(strict_types=1);

namespace app\plugins\weekly;

/**
 * 每周特卖插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'weekly';
    }

    public static function name(): string
    {
        return '每周特卖';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '按周排期上架特卖专场';
    }
}