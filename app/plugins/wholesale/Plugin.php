<?php

declare(strict_types=1);

namespace app\plugins\wholesale;

/**
 * 批发插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'wholesale';
    }

    public static function name(): string
    {
        return '批发';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '阶梯批发价：按数量区间自动计价';
    }
}