<?php

declare(strict_types=1);

namespace app\plugins\stock_holder\controllers;

use app\plugins\ConfigAdminController;

/**
 * 股东分红管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'stock_holder';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\stock_holder\services\StockHolderService::class;
    }
}
