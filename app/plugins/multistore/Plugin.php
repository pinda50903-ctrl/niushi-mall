<?php

declare(strict_types=1);

namespace app\plugins\multistore;

/**
 * 多门店插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'multistore';
    }

    public static function name(): string
    {
        return '多门店';
    }

    public static function category(): string
    {
        return 'store';
    }

    public static function description(): string
    {
        return '门店档案、门店独立库存与营业时间';
    }
}