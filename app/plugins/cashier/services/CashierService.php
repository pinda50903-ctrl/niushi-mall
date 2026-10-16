<?php

declare(strict_types=1);

namespace app\plugins\cashier\services;

/**
 * 门店收银插件：配置定义与读写
 *
 * 配置项：
 *  allow_member_discount  允许会员折扣
 *  receipt_footer         小票底部文案
 *  default_payment        默认支付方式
 */
final class CashierService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'cashier';
    }

    public static function defaults(): array
    {
        return [
        'allow_member_discount' => true,
        'receipt_footer' => '欢迎再次光临',
        'default_payment' => 'wechat',
        ];
    }
}
