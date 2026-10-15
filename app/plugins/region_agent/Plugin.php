<?php

declare(strict_types=1);

namespace app\plugins\region_agent;

/**
 * 区域代理插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'region_agent';
    }

    public static function name(): string
    {
        return '区域代理';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '按区域招募代理、分佣结算';
    }
}