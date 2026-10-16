<?php

declare(strict_types=1);

namespace app\plugins\scan_pay\services;

/**
 * 扫码支付插件：配置定义与读写
 *
 * 配置项：
 *  voice_broadcast        语音播报
 *  auto_check_order       自动核对订单
 *  print_receipt          自动打印小票
 */
final class ScanPayService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'scan_pay';
    }

    public static function defaults(): array
    {
        return [
        'voice_broadcast' => true,
        'auto_check_order' => true,
        'print_receipt' => false,
        ];
    }
}
