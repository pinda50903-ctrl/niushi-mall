<?php

declare(strict_types=1);

namespace app\plugins\store_helper\services;

/**
 * 经营助手插件：配置定义与读写
 *
 * 配置项：
 *  dashboard_days         看板统计天数
 *  todo_remind            待办提醒
 *  low_stock_alert        低库存预警条数
 */
final class StoreHelperService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'store_helper';
    }

    public static function defaults(): array
    {
        return [
        'dashboard_days' => 7,
        'todo_remind' => true,
        'low_stock_alert' => 3,
        ];
    }
}
