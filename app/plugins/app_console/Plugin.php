<?php

declare(strict_types=1);

namespace app\plugins\app_console;

/**
 * 应用控制台插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'app_console';
    }

    public static function name(): string
    {
        return '应用控制台';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '插件运行状态查看与诊断工具';
    }
}