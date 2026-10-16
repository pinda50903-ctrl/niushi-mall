<?php

declare(strict_types=1);

namespace app\plugins\multistore\services;

/**
 * 多门店插件：配置定义与读写
 *
 * 配置项：
 *  max_stores             最大门店数
 *  sync_stock             库存实时同步
 *  auto_select_nearest    自动选择最近门店
 */
final class MultistoreService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'multistore';
    }

    public static function defaults(): array
    {
        return [
        'max_stores' => 20,
        'sync_stock' => true,
        'auto_select_nearest' => true,
        ];
    }
}
