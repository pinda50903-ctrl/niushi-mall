<?php

declare(strict_types=1);

namespace app\plugins\url_scheme\services;

/**
 * 小程序链接插件：配置定义与读写
 *
 * 配置项：
 *  short_len              短码长度
 *  expire_days            有效期(天，0 永久)
 *  track_click            统计点击
 */
final class UrlSchemeService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'url_scheme';
    }

    public static function defaults(): array
    {
        return [
        'short_len' => 8,
        'expire_days' => 0,
        'track_click' => true,
        ];
    }
}
