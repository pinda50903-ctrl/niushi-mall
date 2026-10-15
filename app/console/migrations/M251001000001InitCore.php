<?php

declare(strict_types=1);

namespace app\console\migrations;

use yii\db\Migration;

/**
 * 核心库表初始化（全新 schema，nm_ 前缀）
 */
final class M251001000001InitCore extends Migration
{
    /**
     * 长文本（MySQL 用 longtext，其余用 text）
     */
    private function bigText(): \yii\db\ColumnSchemaBuilder
    {
        if ($this->db->driverName === 'mysql') {
            return $this->db->getSchema()->createColumnSchemaBuilder('longtext');
        }
        return $this->text();
    }

    public function up(): void
    {
        $options = $this->db->driverName === 'mysql'
            ? 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB'
            : null;

        $dec = $this->decimal(12, 2)->notNull()->defaultValue(0);
        $int = $this->integer()->notNull()->defaultValue(0);
        $big = $this->bigInteger()->notNull()->defaultValue(0);
        $stamp = $this->integer()->notNull()->defaultValue(0);
        $stampNull = $this->integer()->null()->defaultValue(null);

        /* ---------- 平台总部 / 租户 ---------- */

        // 租户（商城）
        $this->createTable('{{%tenant}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(64)->notNull(),
            'slug' => $this->string(32)->notNull()->unique(),
            'phone' => $this->string(20)->defaultValue(''),
            'logo' => $this->string(255)->defaultValue(''),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'expire_at' => $stamp,
            'remark' => $this->string(255)->defaultValue(''),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);

        // 平台总部管理员
        $this->createTable('{{%platform_admin}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string(32)->notNull()->unique(),
            'password_hash' => $this->string(128)->notNull(),
            'nickname' => $this->string(32)->defaultValue(''),
            'role_id' => $int,
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'last_login_at' => $stamp,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);

        // 商城管理端账号
        $this->createTable('{{%tenant_admin}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'username' => $this->string(32)->notNull(),
            'password_hash' => $this->string(128)->notNull(),
            'nickname' => $this->string(32)->defaultValue(''),
            'role_id' => $int,
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'last_login_at' => $stamp,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('uk_tenant_admin', '{{%tenant_admin}}', ['tenant_id', 'username'], true);
        $this->createIndex('idx_tenant_admin_role', '{{%tenant_admin}}', 'role_id');

        // 平台角色
        $this->createTable('{{%platform_role}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(32)->notNull(),
            'permissions' => $this->text(),
            'is_super' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'remark' => $this->string(255)->defaultValue(''),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);

        // 租户角色
        $this->createTable('{{%tenant_role}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'name' => $this->string(32)->notNull(),
            'permissions' => $this->text(),
            'is_super' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'remark' => $this->string(255)->defaultValue(''),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_tenant_role_tenant', '{{%tenant_role}}', 'tenant_id');

        // 插件注册表
        $this->createTable('{{%plugin}}', [
            'id' => $this->primaryKey(),
            'code' => $this->string(48)->notNull()->unique(),
            'name' => $this->string(48)->notNull(),
            'category' => $this->string(32)->defaultValue(''),
            'description' => $this->string(255)->defaultValue(''),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);

        // 租户开通插件
        $this->createTable('{{%tenant_plugin}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'code' => $this->string(48)->notNull(),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'expire_at' => $stamp,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('uk_tenant_plugin', '{{%tenant_plugin}}', ['tenant_id', 'code'], true);

        // 操作日志
        $this->createTable('{{%operation_log}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $big,
            'account_type' => $this->string(16)->defaultValue(''),
            'account_id' => $big,
            'route' => $this->string(128)->defaultValue(''),
            'payload' => $this->text(),
            'ip' => $this->string(45)->defaultValue(''),
            'created_at' => $stamp,
        ], $options);
        $this->createIndex('idx_oplog_tenant_time', '{{%operation_log}}', ['tenant_id', 'created_at']);

        /* ---------- 商品 ---------- */

        $this->createTable('{{%category}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'parent_id' => $big,
            'name' => $this->string(32)->notNull(),
            'icon' => $this->string(255)->defaultValue(''),
            'sort' => $int,
            'is_visible' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_category_tenant', '{{%category}}', ['tenant_id', 'parent_id']);

        $this->createTable('{{%goods}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'category_id' => $big,
            'title' => $this->string(128)->notNull(),
            'subtitle' => $this->string(255)->defaultValue(''),
            'cover' => $this->string(255)->defaultValue(''),
            'slides' => $this->text(),
            'detail' => $this->bigText(),
            'unit' => $this->string(8)->defaultValue('件'),
            'price' => $dec,
            'line_price' => $dec,
            'cost_price' => $dec,
            'stock' => $int,
            'weight' => $dec,
            'virtual_sales' => $int,
            'sold_count' => $int,
            'view_count' => $big,
            'is_sku_enabled' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'is_recommend' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'is_free_shipping' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'sort' => $int,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_goods_tenant_cat', '{{%goods}}', ['tenant_id', 'category_id', 'status']);
        $this->createIndex('idx_goods_tenant_status', '{{%goods}}', ['tenant_id', 'status', 'sort']);

        $this->createTable('{{%goods_sku}}', [
            'id' => $this->primaryKey(),
            'goods_id' => $big,
            'spec_values' => $this->string(255)->defaultValue(''),
            'spec_text' => $this->string(128)->defaultValue(''),
            'price' => $dec,
            'line_price' => $dec,
            'stock' => $int,
            'weight' => $dec,
            'code' => $this->string(64)->defaultValue(''),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_sku_goods', '{{%goods_sku}}', 'goods_id');

        $this->createTable('{{%freight_template}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'name' => $this->string(64)->notNull(),
            'charge_type' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'base_amount' => $dec,
            'base_quantity' => $dec,
            'step_amount' => $dec,
            'step_quantity' => $dec,
            'free_order_amount' => $dec,
            'excluded_regions' => $this->text(),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_freight_tenant', '{{%freight_template}}', 'tenant_id');

        /* ---------- 内容 / 附件 ---------- */

        $this->createTable('{{%banner}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'title' => $this->string(64)->defaultValue(''),
            'cover' => $this->string(255)->notNull(),
            'link' => $this->string(255)->defaultValue(''),
            'sort' => $int,
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_banner_tenant', '{{%banner}}', ['tenant_id', 'status', 'sort']);

        $this->createTable('{{%article}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'title' => $this->string(128)->notNull(),
            'cover' => $this->string(255)->defaultValue(''),
            'summary' => $this->string(255)->defaultValue(''),
            'content' => $this->bigText(),
            'sort' => $int,
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_article_tenant', '{{%article}}', ['tenant_id', 'status']);

        $this->createTable('{{%diy_page}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'title' => $this->string(64)->notNull(),
            'config' => $this->bigText(),
            'is_home' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_diy_tenant', '{{%diy_page}}', ['tenant_id', 'is_home', 'status']);

        $this->createTable('{{%setting}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'code' => $this->string(48)->notNull(),
            'value' => $this->bigText(),
        ], $options);
        $this->createIndex('uk_setting', '{{%setting}}', ['tenant_id', 'code'], true);

        $this->createTable('{{%attachment}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'name' => $this->string(128)->defaultValue(''),
            'path' => $this->string(255)->notNull(),
            'url' => $this->string(255)->notNull(),
            'size' => $big,
            'ext' => $this->string(16)->defaultValue(''),
            'storage' => $this->string(16)->notNull()->defaultValue('local'),
            'group_id' => $big,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_attach_tenant', '{{%attachment}}', ['tenant_id', 'created_at']);

        /* ---------- 用户 ---------- */

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'nickname' => $this->string(64)->defaultValue(''),
            'avatar' => $this->string(255)->defaultValue(''),
            'mobile' => $this->string(20)->defaultValue(''),
            'password_hash' => $this->string(128)->defaultValue(''),
            'balance' => $dec,
            'integral' => $int,
            'level' => $int,
            'parent_id' => $big,
            'remark' => $this->string(255)->defaultValue(''),
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_user_tenant', '{{%user}}', ['tenant_id', 'status']);
        $this->createIndex('idx_user_tenant_mobile', '{{%user}}', ['tenant_id', 'mobile']);
        $this->createIndex('idx_user_parent', '{{%user}}', 'parent_id');

        $this->createTable('{{%user_auth}}', [
            'id' => $this->primaryKey(),
            'user_id' => $big,
            'tenant_id' => $int,
            'channel' => $this->string(24)->notNull(),
            'openid' => $this->string(64)->notNull(),
            'unionid' => $this->string(64)->defaultValue(''),
            'credentials' => $this->text(),
        ], $options);
        $this->createIndex('uk_user_auth', '{{%user_auth}}', ['tenant_id', 'channel', 'openid'], true);
        $this->createIndex('idx_user_auth_user', '{{%user_auth}}', 'user_id');

        $this->createTable('{{%user_address}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'user_id' => $big,
            'receiver' => $this->string(32)->notNull(),
            'phone' => $this->string(20)->notNull(),
            'region' => $this->string(128)->defaultValue(''),
            'address' => $this->string(255)->notNull(),
            'is_default' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_addr_user', '{{%user_address}}', ['user_id']);

        $this->createTable('{{%user_favorite}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'user_id' => $big,
            'goods_id' => $big,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('uk_favorite', '{{%user_favorite}}', ['user_id', 'goods_id'], true);

        $this->createTable('{{%browse_log}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'user_id' => $big,
            'goods_id' => $big,
            'created_at' => $stamp,
        ], $options);
        $this->createIndex('idx_browse_user', '{{%browse_log}}', ['user_id', 'created_at']);

        /* ---------- 交易 ---------- */

        $this->createTable('{{%cart}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'user_id' => $big,
            'goods_id' => $big,
            'sku_id' => $big,
            'num' => $int,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_cart_user', '{{%cart}}', ['user_id', 'goods_id', 'sku_id']);

        $this->createTable('{{%order}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'order_no' => $this->string(32)->notNull()->unique(),
            'user_id' => $big,
            'status' => $this->tinyInteger()->notNull()->defaultValue(10),
            'goods_amount' => $dec,
            'freight_amount' => $dec,
            'deduction_amount' => $dec,
            'pay_amount' => $dec,
            'coupon_id' => $big,
            'integral_deduction' => $int,
            'pay_channel' => $this->string(16)->defaultValue(''),
            'paid_at' => $stampNull,
            'delivered_at' => $stampNull,
            'finished_at' => $stampNull,
            'closed_at' => $stampNull,
            'receiver_name' => $this->string(32)->defaultValue(''),
            'receiver_phone' => $this->string(20)->defaultValue(''),
            'receiver_region' => $this->string(128)->defaultValue(''),
            'receiver_address' => $this->string(255)->defaultValue(''),
            'buyer_message' => $this->string(255)->defaultValue(''),
            'seller_remark' => $this->string(255)->defaultValue(''),
            'delivery_type' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'source' => $this->string(24)->defaultValue('weapp'),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_order_tenant_status', '{{%order}}', ['tenant_id', 'status', 'created_at']);
        $this->createIndex('idx_order_user', '{{%order}}', ['user_id', 'status']);

        $this->createTable('{{%order_item}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'order_id' => $big,
            'goods_id' => $big,
            'sku_id' => $big,
            'title' => $this->string(128)->notNull(),
            'cover' => $this->string(255)->defaultValue(''),
            'spec_text' => $this->string(128)->defaultValue(''),
            'unit_price' => $dec,
            'num' => $int,
            'total_amount' => $dec,
            'refund_state' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_item_order', '{{%order_item}}', 'order_id');
        $this->createIndex('idx_item_goods', '{{%order_item}}', ['goods_id', 'created_at']);

        $this->createTable('{{%order_refund}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'refund_no' => $this->string(32)->notNull()->unique(),
            'order_id' => $big,
            'item_id' => $big,
            'user_id' => $big,
            'type' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'reason' => $this->string(64)->defaultValue(''),
            'description' => $this->string(255)->defaultValue(''),
            'voucher' => $this->text(),
            'amount' => $dec,
            'status' => $this->tinyInteger()->notNull()->defaultValue(10),
            'refused_reason' => $this->string(255)->defaultValue(''),
            'return_express_no' => $this->string(32)->defaultValue(''),
            'handled_at' => $stampNull,
            'refunded_at' => $stampNull,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_refund_order', '{{%order_refund}}', ['order_id', 'status']);
        $this->createIndex('idx_refund_tenant_status', '{{%order_refund}}', ['tenant_id', 'status', 'created_at']);

        $this->createTable('{{%payment_record}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'user_id' => $big,
            'order_id' => $big,
            'scene' => $this->string(24)->notNull()->defaultValue('order'),
            'out_trade_no' => $this->string(32)->notNull()->unique(),
            'channel' => $this->string(16)->notNull(),
            'amount' => $dec,
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'trade_no' => $this->string(64)->defaultValue(''),
            'raw_notify' => $this->bigText(),
            'paid_at' => $stampNull,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_pay_tenant_status', '{{%payment_record}}', ['tenant_id', 'status', 'created_at']);

        /* ---------- 营销 ---------- */

        $this->createTable('{{%coupon}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'name' => $this->string(64)->notNull(),
            'kind' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'scope' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'scope_value' => $this->text(),
            'min_amount' => $dec,
            'off_amount' => $dec,
            'off_percent' => $int,
            'stock' => $int,
            'sent_count' => $int,
            'per_limit' => $int,
            'expire_kind' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'valid_from' => $stamp,
            'valid_to' => $stamp,
            'valid_days' => $int,
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_coupon_tenant', '{{%coupon}}', ['tenant_id', 'status']);

        $this->createTable('{{%user_coupon}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'coupon_id' => $big,
            'user_id' => $big,
            'status' => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'expire_at' => $stamp,
            'order_id' => $big,
            'received_at' => $stamp,
            'used_at' => $stampNull,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ], $options);
        $this->createIndex('idx_ucoupon_user', '{{%user_coupon}}', ['user_id', 'status']);
        $this->createIndex('uk_ucoupon_limit', '{{%user_coupon}}', ['coupon_id', 'user_id']);

        /* ---------- 统计 / 基础设施 ---------- */

        $this->createTable('{{%visit_log}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'user_id' => $big,
            'channel' => $this->string(24)->defaultValue(''),
            'path' => $this->string(255)->defaultValue(''),
            'ip' => $this->string(45)->defaultValue(''),
            'created_at' => $stamp,
        ], $options);
        $this->createIndex('idx_visit_tenant_time', '{{%visit_log}}', ['tenant_id', 'created_at']);

        $this->createTable('{{%daily_stat}}', [
            'id' => $this->primaryKey(),
            'tenant_id' => $int,
            'stat_date' => $this->date()->notNull(),
            'new_users' => $int,
            'order_count' => $int,
            'order_amount' => $dec,
            'pay_count' => $int,
            'pay_amount' => $dec,
        ], $options);
        $this->createIndex('uk_daily_stat', '{{%daily_stat}}', ['tenant_id', 'stat_date'], true);

        // 队列表（yii2-queue db 驱动）
        $this->createTable('{{%queue_job}}', [
            'id' => $this->primaryKey(),
            'channel' => $this->string()->notNull(),
            'job' => $this->binary()->notNull(),
            'pushed_at' => $this->integer()->notNull(),
            'ttr' => $this->integer()->notNull(),
            'delay' => $this->integer()->notNull()->defaultValue(0),
            'priority' => $this->integer()->notNull()->defaultValue(1024),
            'reserved_at' => $this->integer(),
            'attempt' => $this->integer(),
            'done_at' => $this->integer(),
        ], $options);
        $this->createIndex('idx_queue_channel', '{{%queue_job}}', 'channel');
        $this->createIndex('idx_queue_reserved', '{{%queue_job}}', 'reserved_at');
        $this->createIndex('idx_queue_pushed', '{{%queue_job}}', 'pushed_at');
        $this->createIndex('idx_queue_done', '{{%queue_job}}', 'done_at');
    }

    public function down(): void
    {
        foreach ([
            'queue_job', 'daily_stat', 'visit_log', 'user_coupon', 'coupon',
            'payment_record', 'order_refund', 'order_item', 'order', 'cart',
            'browse_log', 'user_favorite', 'user_address', 'user_auth', 'user',
            'attachment', 'setting', 'diy_page', 'article', 'banner',
            'freight_template', 'goods_sku', 'goods', 'category',
            'operation_log', 'tenant_plugin', 'plugin', 'tenant_role', 'platform_role',
            'tenant_admin', 'platform_admin', 'tenant',
        ] as $table) {
            $this->dropTable('{{%' . $table . '}}');
        }
    }
}

