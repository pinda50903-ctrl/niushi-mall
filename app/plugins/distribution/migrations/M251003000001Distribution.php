<?php

declare(strict_types=1);

namespace app\plugins\distribution\migrations;

use yii\db\Migration;

/**
 * 分销：推广关系、佣金流水、提现单
 */
final class M251003000001Distribution extends Migration
{
    public function up(): void
    {
        $options = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;
        $dec = $this->decimal(12, 2)->notNull()->defaultValue(0);
        $int = $this->integer()->notNull()->defaultValue(0);
        $stamp = $this->integer()->notNull()->defaultValue(0);

        // 推广关系：每个用户至多一位上级
        $this->createTable('{{%distribution_relation}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'user_id' => $int,
            'parent_id' => $int,
            'bind_at' => $stamp,
            'created_at' => $stamp,
        ], $options);
        $this->createIndex('uk_dist_relation', '{{%distribution_relation}}', ['tenant_id', 'user_id'], true);
        $this->createIndex('idx_dist_parent', '{{%distribution_relation}}', ['tenant_id', 'parent_id']);

        // 佣金流水：0 待结算（订单收货后入账） 1 已入账 2 已失效（退单）
        $this->createTable('{{%distribution_commission}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'user_id' => $int,
            'buyer_id' => $int,
            'order_id' => $int,
            'order_no' => $this->string(32)->defaultValue(''),
            'level' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'amount' => $dec,
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'settle_at' => $stamp,
            'created_at' => $stamp,
        ], $options);
        $this->createIndex('idx_dist_comm_user', '{{%distribution_commission}}', ['tenant_id', 'user_id', 'status']);
        $this->createIndex('uk_dist_comm_order', '{{%distribution_commission}}', ['order_id', 'user_id', 'level'], true);

        // 提现单：0 待审核 1 已打款（入余额） 2 已驳回
        $this->createTable('{{%distribution_withdraw}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'user_id' => $int,
            'amount' => $dec,
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'remark' => $this->string(255)->defaultValue(''),
            'handled_at' => $stamp,
            'created_at' => $stamp,
        ], $options);
        $this->createIndex('idx_dist_wd_user', '{{%distribution_withdraw}}', ['tenant_id', 'user_id', 'status']);
    }

    public function down(): void
    {
        foreach (['distribution_withdraw', 'distribution_commission', 'distribution_relation'] as $t) {
            $this->dropTable('{{%' . $t . '}}');
        }
    }
}
