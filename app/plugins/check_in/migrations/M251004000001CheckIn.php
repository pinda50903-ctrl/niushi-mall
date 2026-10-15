<?php

declare(strict_types=1);

namespace app\plugins\check_in\migrations;

use yii\db\Migration;

/**
 * 签到：打卡记录
 */
final class M251004000001CheckIn extends Migration
{
    public function up(): void
    {
        $options = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;
        $int = $this->integer()->notNull()->defaultValue(0);
        $stamp = $this->integer()->notNull()->defaultValue(0);

        $this->createTable('{{%check_in_record}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'user_id' => $int,
            'ymd' => $int,               // 20261015
            'continuous' => $int,        // 连续天数（补签为 0）
            'reward_integral' => $int,   // 本次奖励积分
            'is_makeup' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $stamp,
        ], $options);
        $this->createIndex('uk_check_in_day', '{{%check_in_record}}', ['tenant_id', 'user_id', 'ymd'], true);
    }

    public function down(): void
    {
        $this->dropTable('{{%check_in_record}}');
    }
}
