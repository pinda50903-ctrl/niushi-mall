<?php

declare(strict_types=1);

namespace app\console\migrations;

use yii\db\Migration;

/**
 * 初始数据：平台超管、角色、插件注册表
 */
final class M251001000002Seed extends Migration
{
    public function up(): void
    {
        $now = time();

        // 平台超管 admin / Admin@2024
        $this->insert('{{%platform_admin}}', [
            'username' => 'admin',
            'password_hash' => password_hash('Admin@2024', PASSWORD_DEFAULT),
            'nickname' => '超级管理员',
            'role_id' => 1,
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->insert('{{%platform_role}}', [
            'name' => '超级管理员',
            'permissions' => '["*"]',
            'is_super' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 插件注册表（code 为本系统插件目录名）
        $plugins = [
            ['groupon', '拼团', '营销'],
            ['seckill', '限时秒杀', '营销'],
            ['flash_sale', '限时抢购', '营销'],
            ['haggle', '砍价', '营销'],
            ['presale', '商品预售', '营销'],
            ['weekly', '每周惠购', '营销'],
            ['wholesale', '整箱批发', '交易'],
            ['bundle', '组合套餐', '交易'],
            ['gift', '满赠活动', '营销'],
            ['exchange', '兑换中心', '营销'],
            ['redpack', '购物红包', '营销'],
            ['lottery', '大转盘抽奖', '营销'],
            ['scratch_card', '刮刮卡', '营销'],
            ['lucky_bag', '福袋', '营销'],
            ['distribution', '分销推广', '分销'],
            ['stock_holder', '股东分红', '分销'],
            ['dividend', '年度分红', '分销'],
            ['points_mall', '积分商城', '营销'],
            ['vip_card', '超级会员卡', '会员'],
            ['member_level', '会员等级', '会员'],
            ['check_in', '每日签到', '互动'],
            ['step_counter', '步数兑换', '互动'],
            ['scrm', '客户管理', '运营'],
            ['multistore', '多商户入驻', '交易'],
            ['cashier', '门店收银', '线下'],
            ['clerk', '店员核销', '线下'],
            ['pickup', '到店自提', '线下'],
            ['scan_pay', '扫码收款', '线下'],
            ['wechat_store', '微信小商店', '渠道'],
            ['booking', '到店预约', '服务'],
            ['form_goods', '表单商品', '交易'],
            ['ecard', '电子卡券', '会员'],
            ['customer_service', '在线客服', '运营'],
            ['quick_share', '快速分享', '运营'],
            ['region_agent', '区域代理', '分销'],
            ['url_scheme', '小程序短链', '渠道'],
            ['phone_link', '电话联系', '运营'],
            ['store_helper', '经营助手', '运营'],
            ['maker', '内容制作', '内容'],
            ['uu_run', '同城跑腿', '服务'],
            ['catalog', '好物清单', '内容'],
            ['sandbox', '示例插件', '其他'],
            ['app_console', '应用控制台', '渠道'],
            ['baidu_app', '百度小程序', '渠道'],
            ['alipay_app', '支付宝小程序', '渠道'],
            ['douyin_app', '抖音小程序', '渠道'],
            ['weapp', '微信小程序', '渠道'],
            ['wechat_mp', '公众号', '渠道'],
            ['h5', 'H5 商城', '渠道'],
            ['video_zone', '短视频专区', '内容'],
        ];
        foreach ($plugins as [$code, $name, $category]) {
            $this->insert('{{%plugin}}', [
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->delete('{{%plugin}}');
        $this->delete('{{%platform_role}}');
        $this->delete('{{%platform_admin}}');
    }
}
