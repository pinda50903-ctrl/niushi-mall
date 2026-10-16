<?php

declare(strict_types=1);

namespace app\plugins\region_agent\services;

/**
 * 区域代理插件：配置定义与读写
 *
 * 配置项：
 *  max_level              代理等级
 *  first_ratio            一级代理佣金
 *  second_ratio           二级代理佣金
 *  protect_region         区域保护
 *  deposit                代理保证金
 */
final class RegionAgentService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'region_agent';
    }

    public static function defaults(): array
    {
        return [
        'max_level' => 3,
        'first_ratio' => 0.2,
        'second_ratio' => 0.1,
        'protect_region' => true,
        'deposit' => 1000,
        ];
    }
}
