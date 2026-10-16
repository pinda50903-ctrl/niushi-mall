<?php

declare(strict_types=1);

namespace app\plugins\wechat_store\services;

/**
 * 微信门店插件：配置定义与读写
 *
 * 配置项：
 *  auto_sync              自动同步门店
 *  show_distance          显示距离
 *  service_hours          营业时间
 */
final class WechatStoreService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'wechat_store';
    }

    public static function defaults(): array
    {
        return [
        'auto_sync' => true,
        'show_distance' => true,
        'service_hours' => '09:00-21:00',
        ];
    }
}
