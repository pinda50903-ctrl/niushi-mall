<?php

declare(strict_types=1);

namespace app\common\services;

use app\common\model\Setting;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * 租户设置：JSON 存取，支持默认值合并
 */
final class SettingService
{
    /** 各设置项默认值 */
    public const DEFAULTS = [
        'base' => [
            'name' => '牛视商城',
            'logo' => '',
            'contact_phone' => '',
            'service_time' => '9:00-18:00',
        ],
        'trade' => [
            'auto_close_minutes' => 30,      // 待付款自动关闭（分钟）
            'auto_receive_days' => 7,        // 发货后自动收货（天）
            'auto_finish_days' => 7,         // 收货后自动完成（天）
            'freight_template_id' => 0,      // 默认运费模板
            'integral_deduction_rate' => 0,  // 每 100 积分抵扣金额
            'order_pay_channels' => ['wechat', 'alipay', 'balance'],
        ],
        'wechat' => [
            'app_id' => '',
            'app_secret' => '',
            'mch_id' => '',
            'pay_key' => '',
        ],
        'alipay' => [
            'app_id' => '',
            'private_key' => '',
            'public_key' => '',
        ],
        'sms' => [
            'driver' => 'log',
            'sign' => '',
            'access_key' => '',
            'secret_key' => '',
        ],
    ];

    public static function get(string $code, int $tenantId): array
    {
        $cacheKey = "setting:{$tenantId}:{$code}";
        $value = Yii::$app->getCache()->getOrSet($cacheKey, static function () use ($tenantId, $code): array {
            $row = Setting::find()
                ->where(['tenant_id' => $tenantId, 'code' => $code])
                ->one();
            $stored = $row ? (json_decode((string)$row->value, true) ?: []) : [];
            return $stored;
        }, 300);
        $defaults = self::DEFAULTS[$code] ?? [];
        return ArrayHelper::merge($defaults, is_array($value) ? $value : []);
    }

    public static function save(string $code, array $value, int $tenantId): void
    {
        $row = Setting::find()
            ->where(['tenant_id' => $tenantId, 'code' => $code])
            ->one();
        if (!$row) {
            $row = new Setting();
            $row->tenant_id = $tenantId;
            $row->code = $code;
        }
        $row->value = json_encode($value, JSON_UNESCAPED_UNICODE);
        if (!$row->save()) {
            throw new \app\common\components\ApiException('设置保存失败', \app\common\components\ApiException::SERVER_ERROR);
        }
        Yii::$app->getCache()->delete("setting:{$tenantId}:{$code}");
    }

    /** 覆盖式更新某设置项的指定键 */
    public static function patch(string $code, array $partial, int $tenantId): array
    {
        $merged = array_merge(self::get($code, $tenantId), $partial);
        self::save($code, $merged, $tenantId);
        return $merged;
    }
}
