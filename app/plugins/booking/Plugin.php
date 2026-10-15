<?php

declare(strict_types=1);

namespace app\plugins\booking;

/**
 * 预约插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'booking';
    }

    public static function name(): string
    {
        return '预约';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '服务预约：时间段库存、预约与取消';
    }
}