<?php

declare(strict_types=1);

namespace app\plugins\points_mall;

/**
 * 积分商城插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'points_mall';
    }

    public static function name(): string
    {
        return '积分商城';
    }

    public static function category(): string
    {
        return 'member';
    }

    public static function description(): string
    {
        return '纯积分兑换商品与积分规则管理';
    }
}