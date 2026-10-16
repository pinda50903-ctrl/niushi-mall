<?php

declare(strict_types=1);

namespace app\plugins\url_scheme\controllers;

use app\plugins\ConfigAdminController;

/**
 * 小程序链接管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'url_scheme';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\url_scheme\services\UrlSchemeService::class;
    }
}
