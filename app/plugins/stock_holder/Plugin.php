<?php

declare(strict_types=1);

namespace app\plugins\stock_holder;

/**
 * 股东分红插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'stock_holder';
    }

    public static function name(): string
    {
        return '股东分红';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '按持股比例定期分红结算';
    }
}