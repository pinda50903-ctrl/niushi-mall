<?php

declare(strict_types=1);

namespace app\plugins\haggle\controllers;

use app\plugins\ConfigAdminController;

/**
 * 好友砍价管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'haggle';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\haggle\services\HaggleService::class;
    }
}
