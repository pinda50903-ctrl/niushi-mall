<?php

declare(strict_types=1);

namespace app\plugins\points_mall\services;

/**
 * 积分商城插件：配置定义与读写
 *
 * 配置项：
 *  exchange_rate          100 积分兑 1 元
 *  free_ship_points       免运费积分门槛
 *  daily_limit            每日兑换次数
 */
final class PointsMallService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'points_mall';
    }

    public static function defaults(): array
    {
        return [
        'exchange_rate' => 100,
        'free_ship_points' => 0,
        'daily_limit' => 5,
        ];
    }
}
