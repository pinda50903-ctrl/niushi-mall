<?php

declare(strict_types=1);

namespace app\plugins\baidu_app\controllers;

use app\plugins\ConfigAdminController;

/**
 * 百度小程序管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'baidu_app';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\baidu_app\services\BaiduAppService::class;
    }
}
