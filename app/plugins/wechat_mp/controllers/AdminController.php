<?php

declare(strict_types=1);

namespace app\plugins\wechat_mp\controllers;

use app\plugins\ConfigAdminController;

/**
 * 微信公众号管理端：插件配置
 *
 * 标准契约：GET config / POST save-config
 */
final class AdminController extends ConfigAdminController
{
    protected function pluginCode(): string
    {
        return 'wechat_mp';
    }

    protected function serviceClass(): string
    {
        return \app\plugins\wechat_mp\services\WechatMpService::class;
    }
}
