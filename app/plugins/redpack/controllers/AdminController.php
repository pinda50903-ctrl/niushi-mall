<?php

declare(strict_types=1);

namespace app\plugins\redpack\controllers;

use app\plugins\ConfigAdminController;

/**
 * 购物红包管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'redpack';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\redpack\services\RedpackService::class;
    }
}
