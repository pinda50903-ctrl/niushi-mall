<?php

declare(strict_types=1);

namespace app\plugins\dividend\services;

/**
 * 团队分红插件：配置定义与读写
 *
 * 配置项：
 *  dividend_ratio         分红比例
 *  settle_cycle           结算周期(day/week/month)
 *  min_team_sales         最低团队业绩
 *  auto_settle            是否自动结算
 */
final class DividendService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'dividend';
    }

    public static function defaults(): array
    {
        return [
        'dividend_ratio' => 0.05,
        'settle_cycle' => 'month',
        'min_team_sales' => 10000,
        'auto_settle' => true,
        ];
    }
}
