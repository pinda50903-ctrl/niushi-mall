<?php

declare(strict_types=1);

namespace app\plugins\pickup;

/**
 * 到店自提插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'pickup';
    }

    public static function name(): string
    {
        return '到店自提';
    }

    public static function category(): string
    {
        return 'store';
    }

    public static function description(): string
    {
        return '自提点管理、自提码核销';
    }
}