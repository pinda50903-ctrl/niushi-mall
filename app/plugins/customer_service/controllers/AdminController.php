<?php

declare(strict_types=1);

namespace app\plugins\customer_service\controllers;

use app\plugins\ConfigAdminController;

/**
 * 在线客服管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'customer_service';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\customer_service\services\CustomerServiceService::class;
    }
}
