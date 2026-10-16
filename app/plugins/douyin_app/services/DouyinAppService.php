<?php

declare(strict_types=1);

namespace app\plugins\douyin_app\services;

/**
 * 抖音小程序插件：配置定义与读写
 *
 * 配置项：
 *  app_id                 抖音 AppID
 *  auto_login             自动登录
 *  share_timeline         支持分享朋友圈
 */
final class DouyinAppService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'douyin_app';
    }

    public static function defaults(): array
    {
        return [
        'app_id' => '',
        'auto_login' => true,
        'share_timeline' => true,
        ];
    }
}
