<?php

declare(strict_types=1);

namespace app\common\services;

use app\common\components\ApiException;
use app\common\model\Coupon;
use app\common\model\UserCoupon;

/**
 * 优惠券：发放、可用性判定、折算
 */
final class CouponService
{
    /** 领取 */
    public static function receive(int $userId, int $tenantId, int $couponId): void
    {
        /** @var Coupon|null $coupon */
        $coupon = Coupon::find()
            ->where(['id' => $couponId, 'tenant_id' => $tenantId, 'status' => 1])
            ->one();
        if (!$coupon) {
            throw new ApiException('优惠券不存在', ApiException::NOT_FOUND);
        }
        $now = time();
        if ((int)$coupon->expire_kind === 1 && ($now < (int)$coupon->valid_from || $now > (int)$coupon->valid_to)) {
            throw new ApiException('活动未开始或已结束', ApiException::VALIDATE);
        }
        if ((int)$coupon->stock > 0 && (int)$coupon->sent_count >= (int)$coupon->stock) {
            throw new ApiException('已被领完', ApiException::VALIDATE);
        }
        if ((int)$coupon->per_limit > 0) {
            $count = (int)UserCoupon::find()
                ->where(['coupon_id' => $couponId, 'user_id' => $userId])
                ->count();
            if ($count >= (int)$coupon->per_limit) {
                throw new ApiException('已达到领取上限', ApiException::VALIDATE);
            }
        }
        $record = new UserCoupon();
        $record->tenant_id = $tenantId;
        $record->coupon_id = $couponId;
        $record->user_id = $userId;
        $record->status = UserCoupon::STATUS_UNUSED;
        $record->expire_at = (int)$coupon->expire_kind === 2 ? $now + (int)$coupon->valid_days * 86400 : (int)$coupon->valid_to;
        $record->received_at = $now;
        if (!$record->save(false)) {
            throw new ApiException('领取失败', ApiException::SERVER_ERROR);
        }
        Coupon::updateAllCounters(['sent_count' => 1], ['id' => $couponId]);
    }

    /**
     * 计算商品集合可用优惠券（返回未使用的持有券及折算结果）
     * @param array $items [['goods_id'=>int,'category_id'=>int,'amount'=>float], ...]
     * @return int[] 可用的 user_coupon id 列表
     */
    public static function usableCoupons(int $userId, int $tenantId, array $items): array
    {
        $holder = UserCoupon::find()
            ->where(['user_id' => $userId, 'status' => UserCoupon::STATUS_UNUSED])
            ->andWhere(['>', 'expire_at', time()])
            ->all();
        $usableIds = [];
        foreach ($holder as $record) {
            /** @var Coupon|null $coupon */
            $coupon = Coupon::find()->where(['id' => $record->coupon_id])->one();
            if (!$coupon || (int)$coupon->tenant_id !== $tenantId) {
                continue;
            }
            $matched = self::matchedAmount($coupon, $items);
            if ($matched >= (float)$coupon->min_amount && $matched > 0) {
                $usableIds[] = (int)$record->id;
            }
        }
        return $usableIds;
    }

    /**
     * 券是否命中商品集合并返回优惠金额（不合法抛异常）
     * @return float 优惠金额
     */
    public static function apply(UserCoupon $record, Coupon $coupon, array $items, float $goodsAmount): float
    {
        if ((int)$record->status !== UserCoupon::STATUS_UNUSED || (int)$record->expire_at < time()) {
            throw new ApiException('优惠券不可用', ApiException::VALIDATE);
        }
        $matched = self::matchedAmount($coupon, $items);
        if ($matched < (float)$coupon->min_amount) {
            throw new ApiException('未满足优惠券使用门槛', ApiException::VALIDATE);
        }
        $deduct = (int)$coupon->kind === Coupon::KIND_REDUCE
            ? (float)$coupon->off_amount
            : round($goodsAmount * (100 - (int)$coupon->off_percent) / 100, 2);
        return min($deduct, $goodsAmount);
    }

    /** 券作用范围内商品金额 */
    private static function matchedAmount(Coupon $coupon, array $items): float
    {
        if ((int)$coupon->scope === Coupon::SCOPE_ALL) {
            return array_sum(array_column($items, 'amount'));
        }
        $scopeIds = GoodsService::jsonArray($coupon->scope_value);
        $sum = 0.0;
        foreach ($items as $item) {
            $hit = (int)$coupon->scope === Coupon::SCOPE_GOODS
                ? in_array((int)$item['goods_id'], $scopeIds, true)
                : in_array((int)$item['category_id'], $scopeIds, true);
            if ($hit) {
                $sum += (float)$item['amount'];
            }
        }
        return $sum;
    }
}
