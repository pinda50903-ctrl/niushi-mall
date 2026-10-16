<?php

declare(strict_types=1);

namespace app\plugins\booking\services;

/**
 * 到店预约插件：配置定义与读写
 *
 * 配置项：
 *  slot_minutes           预约时段(分钟)
 *  advance_days           可提前预约天数
 *  auto_cancel_hours      自动取消时间(小时)
 *  deposit_amount         预约押金
 */
final class BookingService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'booking';
    }

    public static function defaults(): array
    {
        return [
        'slot_minutes' => 30,
        'advance_days' => 7,
        'auto_cancel_hours' => 2,
        'deposit_amount' => 0,
        ];
    }
}
