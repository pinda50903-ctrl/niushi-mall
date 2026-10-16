<?php

declare(strict_types=1);

namespace app\plugins\weapp\controllers;

use app\plugins\ConfigAdminController;

/**
 * 微信小程序管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'weapp';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\weapp\services\WeappService::class;
    }
}
