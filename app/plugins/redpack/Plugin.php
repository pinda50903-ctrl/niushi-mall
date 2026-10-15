<?php

declare(strict_types=1);

namespace app\plugins\redpack;

/**
 * 红包插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'redpack';
    }

    public static function name(): string
    {
        return '红包';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '现金红包发放、领取与余额入账';
    }
}