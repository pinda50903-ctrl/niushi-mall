<?php

declare(strict_types=1);

namespace app\plugins\sandbox\services;

/**
 * 联调沙箱插件：配置定义与读写
 *
 * 配置项：
 *  mock_pay               开启模拟支付
 *  mock_refund            开启模拟退款
 *  auto_close_order       自动关闭超时订单
 */
final class SandboxService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'sandbox';
    }

    public static function defaults(): array
    {
        return [
        'mock_pay' => true,
        'mock_refund' => true,
        'auto_close_order' => false,
        ];
    }
}
