<?php

declare(strict_types=1);

namespace app\plugins\groupon\models;

/** 拼团参团成员 */
final class GrouponMember extends \app\common\db\ActiveRecord
{
    public function behaviors(): array
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => null,
                'value' => time(),
            ],
        ];
    }
}
