<?php

declare(strict_types=1);

namespace app\plugins\phone_link\controllers;

use app\plugins\ConfigAdminController;

/**
 * 电话联系管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'phone_link';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\phone_link\services\PhoneLinkService::class;
    }
}
