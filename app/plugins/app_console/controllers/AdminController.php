<?php

declare(strict_types=1);

namespace app\plugins\app_console\controllers;

use app\plugins\ConfigAdminController;

/**
 * 应用控制台管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'app_console';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\app_console\services\AppConsoleService::class;
    }
}
