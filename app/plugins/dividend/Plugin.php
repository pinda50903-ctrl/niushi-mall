<?php

declare(strict_types=1);

namespace app\plugins\dividend;

/**
 * 团队分红插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'dividend';
    }

    public static function name(): string
    {
        return '团队分红';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '团队业绩分红：等级加权、日结月结';
    }
}