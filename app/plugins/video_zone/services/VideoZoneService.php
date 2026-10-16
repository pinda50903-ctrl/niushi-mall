<?php

declare(strict_types=1);

namespace app\plugins\video_zone\services;

/**
 * 短视频专区插件：配置定义与读写
 *
 * 配置项：
 *  max_video              视频数量上限
 *  auto_cover             自动截取封面
 *  rows                   每行视频数
 */
final class VideoZoneService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'video_zone';
    }

    public static function defaults(): array
    {
        return [
        'max_video' => 100,
        'auto_cover' => true,
        'rows' => 2,
        ];
    }
}
