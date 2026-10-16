<?php

declare(strict_types=1);

namespace app\plugins\exchange\services;

/**
 * 好物兑换插件：配置定义与读写
 *
 * 配置项：
 *  default_points         默认兑换积分
 *  per_limit              每人每月限兑
 *  need_address           兑换需收货地址
 */
final class ExchangeService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'exchange';
    }

    public static function defaults(): array
    {
        return [
        'default_points' => 100,
        'per_limit' => 1,
        'need_address' => true,
        ];
    }
}
