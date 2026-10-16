<?php

declare(strict_types=1);

namespace app\plugins\maker\services;

/**
 * 内容制作插件：配置定义与读写
 *
 * 配置项：
 *  max_material           素材库上限
 *  auto_compress          自动压缩图片
 *  default_template       默认模板
 */
final class MakerService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'maker';
    }

    public static function defaults(): array
    {
        return [
        'max_material' => 200,
        'auto_compress' => true,
        'default_template' => '',
        ];
    }
}
