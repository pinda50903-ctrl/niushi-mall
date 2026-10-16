<?php

declare(strict_types=1);

namespace app\plugins\scratch_card\services;

/**
 * 刮刮卡插件：配置定义与读写
 *
 * 配置项：
 *  cost_points            每次刮奖消耗积分
 *  max_amount             最大中奖金额
 *  daily_limit            每日刮奖次数
 */
final class ScratchCardService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'scratch_card';
    }

    public static function defaults(): array
    {
        return [
        'cost_points' => 5,
        'max_amount' => 50,
        'daily_limit' => 3,
        ];
    }
}
