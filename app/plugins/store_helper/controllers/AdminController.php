<?php

declare(strict_types=1);

namespace app\plugins\store_helper\controllers;

use app\plugins\ConfigAdminController;

/**
 * 经营助手管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'store_helper';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\store_helper\services\StoreHelperService::class;
    }
}
