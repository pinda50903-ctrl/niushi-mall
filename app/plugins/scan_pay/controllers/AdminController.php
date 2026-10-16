<?php

declare(strict_types=1);

namespace app\plugins\scan_pay\controllers;

use app\plugins\ConfigAdminController;

/**
 * 扫码支付管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'scan_pay';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\scan_pay\services\ScanPayService::class;
    }
}
