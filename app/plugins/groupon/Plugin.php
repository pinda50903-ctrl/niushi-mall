<?php

declare(strict_types=1);

namespace app\plugins\groupon;

/**
 * 拼团插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'groupon';
    }

    public static function name(): string
    {
        return '拼团';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '多人成团低价购：团长开团、参团、自动成团与退款';
    }
}