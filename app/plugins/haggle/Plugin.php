<?php

declare(strict_types=1);

namespace app\plugins\haggle;

/**
 * 砍价插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'haggle';
    }

    public static function name(): string
    {
        return '砍价';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '好友助力砍到最低价，裂变获客';
    }
}