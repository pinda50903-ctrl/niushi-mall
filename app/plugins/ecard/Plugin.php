<?php

declare(strict_types=1);

namespace app\plugins\ecard;

/**
 * 电子卡密插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'ecard';
    }

    public static function name(): string
    {
        return '电子卡密';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '卡密商品：导入卡密、购买后自动发货';
    }
}