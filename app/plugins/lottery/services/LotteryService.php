<?php

declare(strict_types=1);

namespace app\plugins\lottery\services;

/**
 * 大转盘抽奖插件：配置定义与读写
 *
 * 配置项：
 *  cost_points            每次抽奖消耗积分
 *  daily_limit            每日抽奖次数
 *  prize_name             最高奖品名称
 *  win_rate               中奖概率
 */
final class LotteryService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'lottery';
    }

    public static function defaults(): array
    {
        return [
        'cost_points' => 10,
        'daily_limit' => 5,
        'prize_name' => '一等奖',
        'win_rate' => 0.1,
        ];
    }
}
