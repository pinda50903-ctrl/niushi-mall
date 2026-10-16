<?php

declare(strict_types=1);

namespace app\plugins\scratch_card\controllers;

use app\plugins\ConfigAdminController;

/**
 * 刮刮卡管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'scratch_card';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\scratch_card\services\ScratchCardService::class;
    }
}
