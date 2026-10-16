<?php

declare(strict_types=1);

namespace app\plugins\multistore\controllers;

use app\plugins\ConfigAdminController;

/**
 * 多门店管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'multistore';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\multistore\services\MultistoreService::class;
    }
}
