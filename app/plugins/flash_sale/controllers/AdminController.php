<?php

declare(strict_types=1);

namespace app\plugins\flash_sale\controllers;

use app\plugins\ConfigAdminController;

/**
 * 限时抢购管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'flash_sale';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\flash_sale\services\FlashSaleService::class;
    }
}
