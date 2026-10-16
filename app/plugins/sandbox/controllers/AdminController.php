<?php

declare(strict_types=1);

namespace app\plugins\sandbox\controllers;

use app\plugins\ConfigAdminController;

/**
 * 联调沙箱管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'sandbox';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\sandbox\services\SandboxService::class;
    }
}
