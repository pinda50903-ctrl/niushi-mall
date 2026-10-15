<?php

declare(strict_types=1);

namespace app\plugins\video_zone;

/**
 * 视频专区插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'video_zone';
    }

    public static function name(): string
    {
        return '视频专区';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '视频内容货架：短视频展示与跳转购买';
    }
}