<?php

declare(strict_types=1);

namespace app\plugins\baidu_app\services;

/**
 * 百度小程序插件：配置定义与读写
 *
 * 配置项：
 *  app_key                百度 AppKey
 *  auto_login             自动登录
 *  enable_component       启用增强组件
 */
final class BaiduAppService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'baidu_app';
    }

    public static function defaults(): array
    {
        return [
        'app_key' => '',
        'auto_login' => true,
        'enable_component' => true,
        ];
    }
}
