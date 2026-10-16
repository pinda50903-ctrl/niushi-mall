<?php

declare(strict_types=1);

namespace app\plugins\vip_card\controllers;

use app\plugins\ConfigAdminController;

/**
 * 超级会员卡管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'vip_card';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\vip_card\services\VipCardService::class;
    }
}
