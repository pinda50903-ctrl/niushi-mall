<?php

declare(strict_types=1);

namespace app\common\services;

use app\common\model\Setting;
use Yii;

/**
 * 插件配置：复用 nm_setting 表，code = plugin:<code>
 */
final class PluginSettingService
{
    public static function get(string $pluginCode, int $tenantId): array
    {
        return SettingService::get('plugin:' . $pluginCode, $tenantId);
    }

    public static function save(string $pluginCode, array $value, int $tenantId): void
    {
        SettingService::save('plugin:' . $pluginCode, $value, $tenantId);
    }
}
