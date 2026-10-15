<?php

declare(strict_types=1);

namespace app\plugins\vip_card;

/**
 * 会员卡插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'vip_card';
    }

    public static function name(): string
    {
        return '会员卡';
    }

    public static function category(): string
    {
        return 'member';
    }

    public static function description(): string
    {
        return '付费会员卡：权益配置、开卡、续费';
    }
}