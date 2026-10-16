<?php

declare(strict_types=1);

namespace app\plugins\uu_run\services;

/**
 * 同城跑腿插件：配置定义与读写
 *
 * 配置项：
 *  radius_km              配送半径(公里)
 *  base_fee               起步价
 *  per_km                 每公里加价
 *  prepare_minutes        接单准备时长
 */
final class UuRunService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'uu_run';
    }

    public static function defaults(): array
    {
        return [
        'radius_km' => 5,
        'base_fee' => 5,
        'per_km' => 2,
        'prepare_minutes' => 30,
        ];
    }
}
