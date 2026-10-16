<?php

declare(strict_types=1);

namespace app\plugins\catalog\services;

/**
 * 产品目录插件：配置定义与读写
 *
 * 配置项：
 *  page_size              每页数量
 *  show_price             显示价格
 *  allow_enquiry          开启询价
 */
final class CatalogService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'catalog';
    }

    public static function defaults(): array
    {
        return [
        'page_size' => 20,
        'show_price' => true,
        'allow_enquiry' => true,
        ];
    }
}
