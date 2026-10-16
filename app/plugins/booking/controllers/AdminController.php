<?php

declare(strict_types=1);

namespace app\plugins\booking\controllers;

use app\plugins\ConfigAdminController;

/**
 * 到店预约管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'booking';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\booking\services\BookingService::class;
    }
}
