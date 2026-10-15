<?php

declare(strict_types=1);

namespace app\plugins\exchange;

/**
 * 好物兑换插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'exchange';
    }

    public static function name(): string
    {
        return '好物兑换';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '积分+现金混合兑换专区';
    }
}