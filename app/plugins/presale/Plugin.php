<?php

declare(strict_types=1);

namespace app\plugins\presale;

/**
 * 预售插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'presale';
    }

    public static function name(): string
    {
        return '预售';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '定金膨胀预售：付定锁货、付尾款发货';
    }
}