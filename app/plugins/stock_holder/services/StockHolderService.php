<?php

declare(strict_types=1);

namespace app\plugins\stock_holder\services;

/**
 * 股东分红插件：配置定义与读写
 *
 * 配置项：
 *  dividend_rate          年化股息率
 *  min_holding            最少持股数
 *  settle_month           派发月份(1-12)
 */
final class StockHolderService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'stock_holder';
    }

    public static function defaults(): array
    {
        return [
        'dividend_rate' => 0.03,
        'min_holding' => 1,
        'settle_month' => 1,
        ];
    }
}
