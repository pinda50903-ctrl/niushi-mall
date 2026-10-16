<?php

declare(strict_types=1);

namespace app\plugins\alipay_app\services;

/**
 * 支付宝小程序插件：配置定义与读写
 *
 * 配置项：
 *  app_id                 支付宝 AppID
 *  auto_login             自动登录
 *  auth_phone             授权手机号
 */
final class AlipayAppService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'alipay_app';
    }

    public static function defaults(): array
    {
        return [
        'app_id' => '',
        'auto_login' => true,
        'auth_phone' => true,
        ];
    }
}
