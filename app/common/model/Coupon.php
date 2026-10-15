<?php

declare(strict_types=1);

namespace app\common\model;

/** 优惠券 */
class Coupon extends \app\common\db\ActiveRecord
{
    public const KIND_REDUCE = 1;
    public const KIND_DISCOUNT = 2;

    public const SCOPE_ALL = 1;
    public const SCOPE_CATEGORY = 2;
    public const SCOPE_GOODS = 3;
}
