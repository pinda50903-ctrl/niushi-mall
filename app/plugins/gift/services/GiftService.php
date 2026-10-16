<?php

declare(strict_types=1);

namespace app\plugins\gift\services;

/**
 * 满赠活动插件：配置定义与读写
 *
 * 配置项：
 *  threshold_amount       满额门槛
 *  gift_name              赠品名称
 *  gift_stock             赠品库存
 */
final class GiftService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'gift';
    }

    public static function defaults(): array
    {
        return [
        'threshold_amount' => 199,
        'gift_name' => '精美小礼品',
        'gift_stock' => 100,
        ];
    }
}
