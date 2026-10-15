<?php

declare(strict_types=1);

namespace app\common\db;

use yii\behaviors\TimestampBehavior;

/**
 * 模型基类：自动 created_at / updated_at（整型秒）
 */
abstract class ActiveRecord extends \yii\db\ActiveRecord
{
    public function behaviors(): array
    {
        // 按 schema 自适应：仅当表存在对应列时才自动维护时间戳（attributes() 即列名列表）
        $columns = $this->attributes();
        $hasCreated = in_array('created_at', $columns, true);
        $hasUpdated = in_array('updated_at', $columns, true);
        if (!$hasCreated && !$hasUpdated) {
            return [];
        }
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => $hasCreated ? 'created_at' : null,
                'updatedAtAttribute' => $hasUpdated ? 'updated_at' : null,
                'value' => time(),
            ],
        ];
    }

    /**
     * 隐藏敏感字段
     */
    public function fields(): array
    {
        $fields = parent::fields();
        foreach (['password_hash', 'credentials'] as $hidden) {
            unset($fields[$hidden]);
        }
        return $fields;
    }
}
