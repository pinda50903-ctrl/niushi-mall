<?php

declare(strict_types=1);

namespace app\plugins\weapp\services;

/**
 * 微信小程序插件：配置定义与读写
 *
 * 配置项：
 *  app_id                 小程序 AppID
 *  mch_id                 商户号
 *  auto_login             自动静默登录
 *  subscribe_message      订阅消息推送
 */
final class WeappService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'weapp';
    }

    public static function defaults(): array
    {
        return [
        'app_id' => '',
        'mch_id' => '',
        'auto_login' => true,
        'subscribe_message' => true,
        ];
    }
}
