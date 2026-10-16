<?php

declare(strict_types=1);

namespace app\plugins\step_counter\services;

/**
 * 步数兑换插件：配置定义与读写
 *
 * 配置项：
 *  daily_steps            每日目标步数
 *  points_per_step        每千步积分
 *  exchange_threshold     兑换所需步数
 */
final class StepCounterService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'step_counter';
    }

    public static function defaults(): array
    {
        return [
        'daily_steps' => 6000,
        'points_per_step' => 1,
        'exchange_threshold' => 100,
        ];
    }
}
