<?php

declare(strict_types=1);

namespace app\plugins\maker;

/**
 * 创客插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'maker';
    }

    public static function name(): string
    {
        return '创客';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '达人/创客招募与作品带货';
    }
}