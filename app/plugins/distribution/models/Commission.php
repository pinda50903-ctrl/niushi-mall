<?php

declare(strict_types=1);

namespace app\plugins\distribution\models;

/** 佣金流水 */
final class Commission extends \app\common\db\ActiveRecord
{
    /** 待结算（等订单完成） */
    public const STATUS_PENDING = 0;
    /** 已入账 */
    public const STATUS_SETTLED = 1;
    /** 已失效（订单退款） */
    public const STATUS_INVALID = 2;

    public static function tableName(): string
    {
        return '{{%distribution_commission}}';
    }
}
