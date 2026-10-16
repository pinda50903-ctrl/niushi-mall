<?php

declare(strict_types=1);

namespace app\plugins\ecard\services;

/**
 * 电子卡密插件：配置定义与读写
 *
 * 配置项：
 *  default_valid_days     默认有效期(天)
 *  auto_generate          下单后自动发卡
 *  allow_refund           允许退款退卡
 */
final class EcardService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'ecard';
    }

    public static function defaults(): array
    {
        return [
        'default_valid_days' => 30,
        'auto_generate' => true,
        'allow_refund' => false,
        ];
    }
}
