<?php

declare(strict_types=1);

namespace app\plugins\uu_run;

/**
 * UU跑腿插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'uu_run';
    }

    public static function name(): string
    {
        return 'UU跑腿';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '同城配送对接：下单、骑手轨迹回调';
    }
}