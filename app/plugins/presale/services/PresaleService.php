<?php

declare(strict_types=1);

namespace app\plugins\presale\services;

/**
 * 商品预售插件：配置定义与读写
 *
 * 配置项：
 *  deposit_ratio          定金比例
 *  deposit_days           尾款支付期限(天)
 *  auto_refund            超时未付尾款自动退款
 */
final class PresaleService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'presale';
    }

    public static function defaults(): array
    {
        return [
        'deposit_ratio' => 0.1,
        'deposit_days' => 7,
        'auto_refund' => true,
        ];
    }
}
