<?php

declare(strict_types=1);

namespace app\plugins\gift\controllers;

use app\plugins\ConfigAdminController;

/**
 * 满赠活动管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'gift';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\gift\services\GiftService::class;
    }
}
