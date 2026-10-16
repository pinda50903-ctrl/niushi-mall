<?php

declare(strict_types=1);

namespace app\plugins\maker\controllers;

use app\plugins\ConfigAdminController;

/**
 * 内容制作管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'maker';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\maker\services\MakerService::class;
    }
}
