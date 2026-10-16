<?php

declare(strict_types=1);

namespace app\plugins\cashier\controllers;

use app\plugins\ConfigAdminController;

/**
 * 门店收银管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'cashier';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\cashier\services\CashierService::class;
    }
}
