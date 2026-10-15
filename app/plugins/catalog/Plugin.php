<?php

declare(strict_types=1);

namespace app\plugins\catalog;

/**
 * 产品目录插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'catalog';
    }

    public static function name(): string
    {
        return '产品目录';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '仅展示不交易的目录型商品库';
    }
}