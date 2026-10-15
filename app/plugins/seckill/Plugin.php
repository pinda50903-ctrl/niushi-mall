<?php

declare(strict_types=1);

namespace app\plugins\seckill;

/**
 * 秒杀插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'seckill';
    }

    public static function name(): string
    {
        return '秒杀';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '整点秒杀场次管理：限时限量、库存独立、自动开抢';
    }
}