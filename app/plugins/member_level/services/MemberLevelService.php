<?php

declare(strict_types=1);

namespace app\plugins\member_level\services;

/**
 * 会员等级插件：配置定义与读写
 *
 * 配置项：
 *  max_level              等级数量
 *  upgrade_amount         每级累计消费门槛
 *  level_discount         会员折扣
 *  birthday_points        生日赠送积分
 */
final class MemberLevelService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'member_level';
    }

    public static function defaults(): array
    {
        return [
        'max_level' => 5,
        'upgrade_amount' => 1000,
        'level_discount' => 0.98,
        'birthday_points' => 100,
        ];
    }
}
