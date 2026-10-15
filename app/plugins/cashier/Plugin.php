<?php

declare(strict_types=1);

namespace app\plugins\cashier;

/**
 * 收银台插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'cashier';
    }

    public static function name(): string
    {
        return '收银台';
    }

    public static function category(): string
    {
        return 'store';
    }

    public static function description(): string
    {
        return '门店扫码收银：收款、退款、日结';
    }
}