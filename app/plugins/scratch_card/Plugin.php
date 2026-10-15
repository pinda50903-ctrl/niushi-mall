<?php

declare(strict_types=1);

namespace app\plugins\scratch_card;

/**
 * 刮刮卡插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'scratch_card';
    }

    public static function name(): string
    {
        return '刮刮卡';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '刮奖互动营销，奖品与中奖记录';
    }
}