<?php

declare(strict_types=1);

namespace app\plugins\pickup\services;

/**
 * 到店自提插件：配置定义与读写
 *
 * 配置项：
 *  prepare_minutes        备货时长(分钟)
 *  auto_notify            到货自动通知
 *  keep_days              超期未取保留天数
 */
final class PickupService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'pickup';
    }

    public static function defaults(): array
    {
        return [
        'prepare_minutes' => 30,
        'auto_notify' => true,
        'keep_days' => 3,
        ];
    }
}
