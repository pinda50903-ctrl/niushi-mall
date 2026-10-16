<?php

declare(strict_types=1);

namespace app\plugins\catalog\controllers;

use app\plugins\ConfigAdminController;

/**
 * 产品目录管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'catalog';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\catalog\services\CatalogService::class;
    }
}
