<?php

declare(strict_types=1);

namespace app\plugins\h5\services;

/**
 * 手机网站插件：配置定义与读写
 *
 * 配置项：
 *  site_title             站点标题
 *  share_desc             分享描述
 *  app_deeplink           唤起 App 链接
 *  force_mobile           强制移动端
 */
final class H5Service extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'h5';
    }

    public static function defaults(): array
    {
        return [
        'site_title' => '牛视商城',
        'share_desc' => '精选好物，正品保障',
        'app_deeplink' => '',
        'force_mobile' => true,
        ];
    }
}
