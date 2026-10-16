<?php

declare(strict_types=1);

namespace app\plugins\quick_share\services;

/**
 * 快速推广插件：配置定义与读写
 *
 * 配置项：
 *  poster_width           海报宽度
 *  poster_height          海报高度
 *  default_channel        默认渠道
 *  track_conversion       统计转化
 */
final class QuickShareService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'quick_share';
    }

    public static function defaults(): array
    {
        return [
        'poster_width' => 750,
        'poster_height' => 1200,
        'default_channel' => 'wechat',
        'track_conversion' => true,
        ];
    }
}
