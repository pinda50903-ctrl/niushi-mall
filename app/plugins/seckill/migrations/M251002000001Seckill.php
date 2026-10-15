<?php

declare(strict_types=1);

namespace app\plugins\seckill\migrations;

use yii\db\Migration;

/**
 * 秒杀：场次与场次商品
 */
final class M251002000001Seckill extends Migration
{
    public function up(): void
    {
        $options = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;
        $dec = $this->decimal(12, 2)->notNull()->defaultValue(0);
        $int = $this->integer()->notNull()->defaultValue(0);
        $stamp = $this->integer()->notNull()->defaultValue(0);

        $this->createTable('{{%seckill_session}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'name' => $this->string(64)->notNull(),
            'start_at' => $stamp,
            'end_at' => $stamp,
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_seckill_session', '{{%seckill_session}}', ['tenant_id', 'status']);

        $this->createTable('{{%seckill_goods}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'session_id' => $int,
            'goods_id' => $int,
            'seckill_price' => $dec,
            'seckill_stock' => $int,
            'sold_count' => $int->defaultValue(0),
            'per_limit' => $int->defaultValue(1),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_seckill_goods', '{{%seckill_goods}}', ['session_id', 'goods_id']);
    }

    public function down(): void
    {
        foreach (['seckill_goods', 'seckill_session'] as $t) {
            $this->dropTable('{{%' . $t . '}}');
        }
    }
}
