<?php

declare(strict_types=1);

namespace app\plugins\alipay_app\controllers;

use app\plugins\ConfigAdminController;

/**
 * 支付宝小程序管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'alipay_app';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\alipay_app\services\AlipayAppService::class;
    }
}
