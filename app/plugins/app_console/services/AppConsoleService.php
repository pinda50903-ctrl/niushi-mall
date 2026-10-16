<?php

declare(strict_types=1);

namespace app\plugins\app_console\services;

/**
 * 应用控制台插件：配置定义与读写
 *
 * 配置项：
 *  auto_upload            自动上传
 *  keep_version           保留版本数
 *  notify_wechat          审核结果通知
 */
final class AppConsoleService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'app_console';
    }

    public static function defaults(): array
    {
        return [
        'auto_upload' => false,
        'keep_version' => 5,
        'notify_wechat' => true,
        ];
    }
}
