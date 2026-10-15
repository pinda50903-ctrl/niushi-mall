<?php

declare(strict_types=1);

namespace app\plugins\groupon\migrations;

use yii\db\Migration;

/**
 * 拼团：活动与开团记录
 */
final class M251002000001Groupon extends Migration
{
    public function up(): void
    {
        $options = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;
        $dec = $this->decimal(12, 2)->notNull()->defaultValue(0);
        $int = $this->integer()->notNull()->defaultValue(0);
        $stamp = $this->integer()->notNull()->defaultValue(0);

        $this->createTable('{{%groupon_activity}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'goods_id' => $int,
            'title' => $this->string(128)->defaultValue(''),
            'group_price' => $dec,
            'group_size' => $int,
            'time_limit_hours' => $int,
            'stock' => $int,
            'virtual_leader' => $int,
            'start_at' => $stamp,
            'end_at' => $stamp,
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_groupon_act', '{{%groupon_activity}}', ['tenant_id', 'status', 'end_at']);

        $this->createTable('{{%groupon_team}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'activity_id' => $int,
            'order_id' => $int,
            'leader_user_id' => $int,
            'need_count' => $int,
            'joined_count' => $int->defaultValue(1),
            'expire_at' => $stamp,
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_groupon_team', '{{%groupon_team}}', ['tenant_id', 'status', 'expire_at']);

        $this->createTable('{{%groupon_member}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'team_id' => $int,
            'user_id' => $int,
            'order_id' => $int,
            'is_leader' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $stamp,
        ], $options);
        $this->createIndex('uk_groupon_member', '{{%groupon_member}}', ['team_id', 'user_id'], true);
    }

    public function down(): void
    {
        foreach (['groupon_member', 'groupon_team', 'groupon_activity'] as $t) {
            $this->dropTable('{{%' . $t . '}}');
        }
    }
}
