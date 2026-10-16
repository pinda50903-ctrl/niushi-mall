<?php

declare(strict_types=1);

namespace app\plugins\scrm\controllers;

use app\plugins\ConfigAdminController;

/**
 * 客户管理管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'scrm';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\scrm\services\ScrmService::class;
    }
}
