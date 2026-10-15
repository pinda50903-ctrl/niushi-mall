<?php

declare(strict_types=1);

namespace app\plugins\bundle;

/**
 * 组合套餐插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'bundle';
    }

    public static function name(): string
    {
        return '组合套餐';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '多商品打包一口价销售';
    }
}