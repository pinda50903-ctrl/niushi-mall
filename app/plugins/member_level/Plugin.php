<?php

declare(strict_types=1);

namespace app\plugins\member_level;

/**
 * 会员等级插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'member_level';
    }

    public static function name(): string
    {
        return '会员等级';
    }

    public static function category(): string
    {
        return 'member';
    }

    public static function description(): string
    {
        return '按消费/成长值自动升降级与等级权益';
    }
}