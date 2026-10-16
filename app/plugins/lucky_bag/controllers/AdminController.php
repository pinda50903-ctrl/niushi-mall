<?php

declare(strict_types=1);

namespace app\plugins\lucky_bag\controllers;

use app\plugins\ConfigAdminController;

/**
 * 福袋管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'lucky_bag';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\lucky_bag\services\LuckyBagService::class;
    }
}
