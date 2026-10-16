<?php

declare(strict_types=1);

namespace app\plugins\clerk\controllers;

use app\plugins\ConfigAdminController;

/**
 * 店员核销管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'clerk';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\clerk\services\ClerkService::class;
    }
}
