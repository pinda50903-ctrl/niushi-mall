<?php

declare(strict_types=1);

namespace app\plugins\lottery\controllers;

use app\plugins\ConfigAdminController;

/**
 * 大转盘抽奖管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'lottery';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\lottery\services\LotteryService::class;
    }
}
