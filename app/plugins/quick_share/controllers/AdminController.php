<?php

declare(strict_types=1);

namespace app\plugins\quick_share\controllers;

use app\plugins\ConfigAdminController;

/**
 * 快速推广管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'quick_share';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\quick_share\services\QuickShareService::class;
    }
}
