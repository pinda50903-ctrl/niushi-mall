<?php

declare(strict_types=1);

namespace app\plugins\step_counter\controllers;

use app\plugins\ConfigAdminController;

/**
 * 步数兑换管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'step_counter';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\step_counter\services\StepCounterService::class;
    }
}
