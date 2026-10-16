<?php

declare(strict_types=1);

namespace app\plugins\customer_service\services;

/**
 * 在线客服插件：配置定义与读写
 *
 * 配置项：
 *  welcome_text           欢迎语
 *  auto_reply             启用自动回复
 *  service_time           客服时间
 */
final class CustomerServiceService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'customer_service';
    }

    public static function defaults(): array
    {
        return [
        'welcome_text' => '您好，有什么可以帮您？',
        'auto_reply' => true,
        'service_time' => '09:00-21:00',
        ];
    }
}
