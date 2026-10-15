<?php

declare(strict_types=1);

namespace app\plugins\distribution;

/**
 * 分销插件
 */
final class Plugin extends \app\plugins\PluginBase
{
    public static function code(): string
    {
        return 'distribution';
    }

    public static function name(): string
    {
        return '分销';
    }

    public static function category(): string
    {
        return 'marketing';
    }

    public static function description(): string
    {
        return '三级分销返佣：绑定关系、佣金结算、提现审核';
    }

    /** 注册订单支付/收货事件，驱动佣金产生与入账 */
    public static function bootstrap(): void
    {
        \app\plugins\distribution\services\CommissionService::listen();
    }
}