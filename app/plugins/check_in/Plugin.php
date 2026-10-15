<?php

declare(strict_types=1);

namespace app\plugins\check_in;

/**
 * 签到插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'check_in';
    }

    public static function name(): string
    {
        return '签到';
    }

    public static function category(): string
    {
        return 'member';
    }

    public static function description(): string
    {
        return '每日签到送积分：连续奖励、补签卡';
    }
}