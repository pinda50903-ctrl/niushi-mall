<?php

declare(strict_types=1);

namespace app\plugins\flash_sale\services;

/**
 * 限时抢购插件：配置定义与读写
 *
 * 配置项：
 *  session_name           场次名称
 *  duration_hours         单场时长(小时)
 *  per_limit              每人限购
 *  need_deposit           是否需要保证金
 */
final class FlashSaleService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'flash_sale';
    }

    public static function defaults(): array
    {
        return [
        'session_name' => '限时抢购场',
        'duration_hours' => 24,
        'per_limit' => 1,
        'need_deposit' => false,
        ];
    }
}
