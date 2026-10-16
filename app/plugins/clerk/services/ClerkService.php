<?php

declare(strict_types=1);

namespace app\plugins\clerk\services;

/**
 * 店员核销插件：配置定义与读写
 *
 * 配置项：
 *  allow_refund           允许店员退款
 *  allow_discount         允许店员改价
 *  daily_report           每日营业报表
 */
final class ClerkService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'clerk';
    }

    public static function defaults(): array
    {
        return [
        'allow_refund' => false,
        'allow_discount' => false,
        'daily_report' => true,
        ];
    }
}
