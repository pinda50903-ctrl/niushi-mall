<?php

declare(strict_types=1);

namespace app\plugins\presale\controllers;

use app\plugins\ConfigAdminController;

/**
 * 商品预售管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'presale';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\presale\services\PresaleService::class;
    }
}
