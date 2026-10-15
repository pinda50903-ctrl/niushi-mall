<?php

declare(strict_types=1);

namespace app\plugins\clerk;

/**
 * 核销员插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'clerk';
    }

    public static function name(): string
    {
        return '核销员';
    }

    public static function category(): string
    {
        return 'store';
    }

    public static function description(): string
    {
        return '核销员账号管理与券码核销记录';
    }
}