<?php

declare(strict_types=1);

namespace app\plugins\scrm\services;

/**
 * 客户管理插件：配置定义与读写
 *
 * 配置项：
 *  auto_tag_count         自动标签数
 *  follow_up_days         回访周期(天)
 *  inactive_days          流失预警天数
 *  enable_sms             启用短信触达
 */
final class ScrmService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'scrm';
    }

    public static function defaults(): array
    {
        return [
        'auto_tag_count' => 3,
        'follow_up_days' => 30,
        'inactive_days' => 90,
        'enable_sms' => false,
        ];
    }
}
