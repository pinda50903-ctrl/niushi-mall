<?php

declare(strict_types=1);

namespace app\plugins\form_goods;

/**
 * 表单商品插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'form_goods';
    }

    public static function name(): string
    {
        return '表单商品';
    }

    public static function category(): string
    {
        return 'tool';
    }

    public static function description(): string
    {
        return '自定义表单商品：报名、调查、定制信息采集';
    }
}