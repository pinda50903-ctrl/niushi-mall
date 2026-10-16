<?php

declare(strict_types=1);

namespace app\plugins\wholesale\services;

/**
 * 整箱批发插件：配置定义与读写
 *
 * 配置项：
 *  box_quantity           每箱数量
 *  min_box                最少起订箱数
 *  tier_discount          阶梯折扣
 */
final class WholesaleService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'wholesale';
    }

    public static function defaults(): array
    {
        return [
        'box_quantity' => 12,
        'min_box' => 1,
        'tier_discount' => 0.9,
        ];
    }
}
