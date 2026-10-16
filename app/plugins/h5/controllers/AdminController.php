<?php

declare(strict_types=1);

namespace app\plugins\h5\controllers;

use app\plugins\ConfigAdminController;

/**
 * 手机网站管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'h5';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\h5\services\H5Service::class;
    }
}
