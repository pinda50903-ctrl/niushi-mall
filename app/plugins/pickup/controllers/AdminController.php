<?php

declare(strict_types=1);

namespace app\plugins\pickup\controllers;

use app\plugins\ConfigAdminController;

/**
 * 到店自提管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'pickup';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\pickup\services\PickupService::class;
    }
}
