<?php

declare(strict_types=1);

namespace app\plugins\form_goods\controllers;

use app\plugins\ConfigAdminController;

/**
 * 表单商品管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'form_goods';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\form_goods\services\FormGoodsService::class;
    }
}
