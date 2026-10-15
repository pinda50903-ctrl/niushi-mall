<?php

declare(strict_types=1);

namespace app\plugins\flash_sale;

/**
 * 限时抢购插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'flash_sale';
    }

    public static function name(): string
    {
        return '限时抢购';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '限时直降活动：倒计时抢购与库存保护';
    }
}