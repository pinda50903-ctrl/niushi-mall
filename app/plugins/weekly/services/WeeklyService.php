<?php

declare(strict_types=1);

namespace app\plugins\weekly\services;

/**
 * 每周特卖插件：配置定义与读写
 *
 * 配置项：
 *  week_days              特卖星期(1-7，逗号分隔)
 *  discount               特卖折扣
 *  start_hour             每日开抢小时
 *  duration_hours         单场时长(小时)
 */
final class WeeklyService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'weekly';
    }

    public static function defaults(): array
    {
        return [
        'week_days' => '1,3,5',
        'discount' => 0.8,
        'start_hour' => 10,
        'duration_hours' => 12,
        ];
    }
}
