<?php

declare(strict_types=1);

namespace app\plugins\step_counter;

/**
 * 步数兑换插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'step_counter';
    }

    public static function name(): string
    {
        return '步数兑换';
    }

    public static function category(): string
    {
        return 'member';
    }

    public static function description(): string
    {
        return '微信步数换积分，健康激励';
    }
}