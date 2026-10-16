<?php

declare(strict_types=1);

namespace app\plugins\redpack\services;

/**
 * 购物红包插件：配置定义与读写
 *
 * 配置项：
 *  min_amount             最小面额
 *  max_amount             最大面额
 *  total_count            发放总量
 *  per_limit              每人每日限领
 */
final class RedpackService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'redpack';
    }

    public static function defaults(): array
    {
        return [
        'min_amount' => 1,
        'max_amount' => 20,
        'total_count' => 500,
        'per_limit' => 1,
        ];
    }
}
