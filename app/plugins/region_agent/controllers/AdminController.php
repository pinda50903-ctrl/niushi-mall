<?php

declare(strict_types=1);

namespace app\plugins\region_agent\controllers;

use app\plugins\ConfigAdminController;

/**
 * 区域代理管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'region_agent';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\region_agent\services\RegionAgentService::class;
    }
}
