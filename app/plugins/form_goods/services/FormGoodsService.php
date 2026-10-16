<?php

declare(strict_types=1);

namespace app\plugins\form_goods\services;

/**
 * 表单商品插件：配置定义与读写
 *
 * 配置项：
 *  max_fields             最大表单字段数
 *  require_phone          手机号必填
 *  auto_remark            表单内容写入订单备注
 */
final class FormGoodsService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'form_goods';
    }

    public static function defaults(): array
    {
        return [
        'max_fields' => 10,
        'require_phone' => true,
        'auto_remark' => true,
        ];
    }
}
