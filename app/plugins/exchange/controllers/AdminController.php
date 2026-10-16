<?php

declare(strict_types=1);

namespace app\plugins\exchange\controllers;

use app\plugins\ConfigAdminController;

/**
 * 好物兑换管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'exchange';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\exchange\services\ExchangeService::class;
    }
}
