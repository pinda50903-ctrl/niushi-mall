<?php

declare(strict_types=1);

namespace app\plugins\wechat_mp\services;

/**
 * 微信公众号插件：配置定义与读写
 *
 * 配置项：
 *  app_id                 公众号 AppID
 *  auto_menu              自动同步菜单
 *  template_message       模板消息
 *  oauth_scope            网页授权范围
 */
final class WechatMpService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'wechat_mp';
    }

    public static function defaults(): array
    {
        return [
        'app_id' => '',
        'auto_menu' => true,
        'template_message' => true,
        'oauth_scope' => 'snsapi_base',
        ];
    }
}
