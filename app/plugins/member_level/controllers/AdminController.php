<?php

declare(strict_types=1);

namespace app\plugins\member_level\controllers;

use app\plugins\ConfigAdminController;

/**
 * 会员等级管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'member_level';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\member_level\services\MemberLevelService::class;
    }
}
