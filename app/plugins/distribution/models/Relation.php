<?php

declare(strict_types=1);

namespace app\plugins\distribution\models;

/** 推广关系（上下级绑定） */
final class Relation extends \app\common\db\ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%distribution_relation}}';
    }
}
