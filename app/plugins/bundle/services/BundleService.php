<?php

declare(strict_types=1);

namespace app\plugins\bundle\services;

/**
 * 组合套餐插件：配置定义与读写
 *
 * 配置项：
 *  max_items              单套餐最多商品数
 *  bundle_discount        组合折扣
 *  stock_alert            库存预警阈值
 */
final class BundleService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'bundle';
    }

    public static function defaults(): array
    {
        return [
        'max_items' => 5,
        'bundle_discount' => 0.95,
        'stock_alert' => 5,
        ];
    }
}
