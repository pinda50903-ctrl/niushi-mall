<?php

declare(strict_types=1);

namespace app\plugins\points_mall\controllers;

use app\plugins\ConfigAdminController;

/**
 * 积分商城管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'points_mall';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\points_mall\services\PointsMallService::class;
    }
}
