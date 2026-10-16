<?php

declare(strict_types=1);

namespace app\plugins\dividend\controllers;

use app\plugins\ConfigAdminController;

/**
 * 团队分红管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'dividend';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\dividend\services\DividendService::class;
    }
}
