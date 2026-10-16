<?php

declare(strict_types=1);

namespace app\plugins\ecard\controllers;

use app\plugins\ConfigAdminController;

/**
 * 电子卡密管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'ecard';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\ecard\services\EcardService::class;
    }
}
