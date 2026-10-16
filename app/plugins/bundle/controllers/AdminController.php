<?php

declare(strict_types=1);

namespace app\plugins\bundle\controllers;

use app\plugins\ConfigAdminController;

/**
 * 组合套餐管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'bundle';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\bundle\services\BundleService::class;
    }
}
