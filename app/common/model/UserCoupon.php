<?php

declare(strict_types=1);

namespace app\common\model;

/** 用户持有优惠券 */
class UserCoupon extends \app\common\db\ActiveRecord
{
    public const STATUS_UNUSED = 1;
    public const STATUS_USED = 2;
    public const STATUS_EXPIRED = 3;
}
