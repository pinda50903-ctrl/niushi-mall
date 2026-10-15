<?php

declare(strict_types=1);

namespace app\plugins\distribution\models;

/** 佣金提现单 */
final class Withdraw extends \app\common\db\ActiveRecord
{
    public const STATUS_PENDING = 0;
    public const STATUS_PAID = 1;
    public const STATUS_REFUSED = 2;

    public static function tableName(): string
    {
        return '{{%distribution_withdraw}}';
    }
}
