<?php

declare(strict_types=1);

namespace app\plugins\douyin_app\controllers;

use app\plugins\ConfigAdminController;

/**
 * 抖音小程序管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'douyin_app';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\douyin_app\services\DouyinAppService::class;
    }
}
