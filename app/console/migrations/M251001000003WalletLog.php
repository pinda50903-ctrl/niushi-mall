<?php

declare(strict_types=1);

namespace app\console\migrations;

use yii\db\Migration;

/**
 * 用户钱包/积分流水
 */
final class M251001000003WalletLog extends Migration
{
    public function up(): void
    {
        $options = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $this->createTable('{{%balance_log}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $this->integer()->notNull()->defaultValue(0),
            'user_id' => $this->bigInteger()->notNull()->defaultValue(0),
            'scene' => $this->string(32)->notNull(),
            'direction' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'amount' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'balance_after' => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'related_no' => $this->string(32)->defaultValue(''),
            'remark' => $this->string(255)->defaultValue(''),
            'created_at' => $this->integer()->notNull()->defaultValue(0),
            'updated_at' => $this->integer()->notNull()->defaultValue(0),
        ], $options);
        $this->createIndex('idx_blog_user', '{{%balance_log}}', ['user_id', 'created_at']);

        $this->createTable('{{%integral_log}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $this->integer()->notNull()->defaultValue(0),
            'user_id' => $this->bigInteger()->notNull()->defaultValue(0),
            'scene' => $this->string(32)->notNull(),
            'direction' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'num' => $this->integer()->notNull()->defaultValue(0),
            'balance_after' => $this->integer()->notNull()->defaultValue(0),
            'related_no' => $this->string(32)->defaultValue(''),
            'remark' => $this->string(255)->defaultValue(''),
            'created_at' => $this->integer()->notNull()->defaultValue(0),
            'updated_at' => $this->integer()->notNull()->defaultValue(0),
        ], $options);
        $this->createIndex('idx_ilog_user', '{{%integral_log}}', ['user_id', 'created_at']);
    }

    public function down(): void
    {
        $this->dropTable('{{%integral_log}}');
        $this->dropTable('{{%balance_log}}');
    }
}
