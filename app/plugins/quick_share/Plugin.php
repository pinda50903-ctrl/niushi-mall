<?php

declare(strict_types=1);

namespace app\plugins\quick_share;

/**
 * 快速推广插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'quick_share';
    }

    public static function name(): string
    {
        return '快速推广';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '商品海报与推广码生成';
    }
}