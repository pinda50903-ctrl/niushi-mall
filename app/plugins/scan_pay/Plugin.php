<?php

declare(strict_types=1);

namespace app\plugins\scan_pay;

/**
 * 扫码支付插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'scan_pay';
    }

    public static function name(): string
    {
        return '扫码支付';
    }

    public static function category(): string
    {
        return 'store';
    }

    public static function description(): string
    {
        return '商家收款码、金额快捷支付';
    }
}