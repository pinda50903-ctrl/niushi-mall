<?php

declare(strict_types=1);

namespace app\plugins\wholesale\controllers;

use app\plugins\ConfigAdminController;

/**
 * 整箱批发管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'wholesale';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\wholesale\services\WholesaleService::class;
    }
}
