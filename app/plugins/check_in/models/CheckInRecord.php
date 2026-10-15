<?php

declare(strict_types=1);

namespace app\plugins\check_in\models;

/** 签到记录 */
final class CheckInRecord extends \app\common\db\ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%check_in_record}}';
    }
}
