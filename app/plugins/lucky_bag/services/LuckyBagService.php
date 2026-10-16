<?php

declare(strict_types=1);

namespace app\plugins\lucky_bag\services;

/**
 * 福袋插件：配置定义与读写
 *
 * 配置项：
 *  min_discount           最低折扣
 *  max_discount           最高折扣
 *  stock                  福袋库存
 *  per_day_limit          每人每日限抽
 */
final class LuckyBagService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'lucky_bag';
    }

    public static function defaults(): array
    {
        return [
        'min_discount' => 0.5,
        'max_discount' => 0.95,
        'stock' => 100,
        'per_day_limit' => 1,
        ];
    }
}
