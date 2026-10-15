<?php

declare(strict_types=1);

namespace app\common\model;

/** 支付流水 */
class PaymentRecord extends \app\common\db\ActiveRecord
{
    public const STATUS_PENDING = 0;
    public const STATUS_PAID = 1;
    public const STATUS_CLOSED = 2;
    public const STATUS_REFUNDED = 3;
}
