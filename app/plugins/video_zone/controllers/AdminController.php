<?php

declare(strict_types=1);

namespace app\plugins\video_zone\controllers;

use app\plugins\ConfigAdminController;

/**
 * 短视频专区管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'video_zone';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\video_zone\services\VideoZoneService::class;
    }
}
