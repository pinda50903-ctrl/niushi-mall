<?php

declare(strict_types=1);

namespace app\common\model;

/** 商品 */
class Goods extends \app\common\db\ActiveRecord
{
    public const STATUS_OFF = 0;
    public const STATUS_ON = 1;
}
