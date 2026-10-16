<?php

declare(strict_types=1);

namespace app\plugins\haggle\services;

/**
 * 好友砍价插件：配置定义与读写
 *
 * 配置项：
 *  min_price              最低可砍价
 *  valid_hours            砍价有效期(小时)
 *  first_cut              首刀金额
 *  success_reward_points  成功奖励积分
 */
final class HaggleService extends \app\plugins\ConfigurableService
{
    public static function code(): string
    {
        return 'haggle';
    }

    public static function defaults(): array
    {
        return [
        'min_price' => 0.01,
        'valid_hours' => 24,
        'first_cut' => 5,
        'success_reward_points' => 10,
        ];
    }
}
