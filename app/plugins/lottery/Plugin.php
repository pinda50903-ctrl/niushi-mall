<?php

declare(strict_types=1);

namespace app\plugins\lottery;

/**
 * 大转盘插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'lottery';
    }

    public static function name(): string
    {
        return '大转盘';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '九宫格/大转盘抽奖：概率、库存、奖品核销';
    }
}