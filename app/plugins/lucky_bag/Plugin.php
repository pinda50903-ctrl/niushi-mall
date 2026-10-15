<?php

declare(strict_types=1);

namespace app\plugins\lucky_bag;

/**
 * 福袋插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'lucky_bag';
    }

    public static function name(): string
    {
        return '福袋';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '随机福袋：付款后随机开出商品';
    }
}