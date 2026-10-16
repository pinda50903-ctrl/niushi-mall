<?php

declare(strict_types=1);

namespace app\plugins\vip_card\services;

/**
 * 超级会员卡插件：配置定义与读写
 *
 * 配置项：
 *  card_price             会员卡售价
 *  valid_days             有效期(天)
 *  discount               会员折扣
 *  monthly_points         每月赠送积分
 */
final class VipCardService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'vip_card';
    }

    public static function defaults(): array
    {
        return [
        'card_price' => 99,
        'valid_days' => 365,
        'discount' => 0.9,
        'monthly_points' => 100,
        ];
    }
}
