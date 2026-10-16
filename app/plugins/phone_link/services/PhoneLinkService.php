<?php

declare(strict_types=1);

namespace app\plugins\phone_link\services;

/**
 * 电话联系插件：配置定义与读写
 *
 * 配置项：
 *  service_phone          客服电话
 *  show_float             显示拨号浮层
 *  work_time              服务时间
 */
final class PhoneLinkService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'phone_link';
    }

    public static function defaults(): array
    {
        return [
        'service_phone' => '',
        'show_float' => true,
        'work_time' => '09:00-21:00',
        ];
    }
}
