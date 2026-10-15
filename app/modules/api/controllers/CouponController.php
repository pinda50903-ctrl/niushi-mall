<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\common\components\ApiException;
use app\common\model\Coupon;
use app\common\model\UserCoupon;
use app\common\services\CouponService;

/**
 * 优惠券（用户端）
 */
final class CouponController extends UserAwareController
{
    /** 可领取中心 */
    public function actionCenter(): \yii\web\Response
    {
        $tenantId = $this->tenantId();
        $userId = $this->currentUser() && (int)$this->currentUser()->id > 0 ? (int)$this->currentUser()->id : 0;
        $now = time();
        $rows = Coupon::find()
            ->where(['tenant_id' => $tenantId, 'status' => 1])
            ->andWhere(['or', ['expire_kind' => 2], ['and', ['<=', 'valid_from', $now], ['>=', 'valid_to', $now]]])
            ->orderBy('id DESC')
            ->all();

        $receivedMap = [];
        if ($userId > 0) {
            foreach (UserCoupon::find()->where(['user_id' => $userId])->all() as $record) {
                /** @var UserCoupon $record */
                $receivedMap[(int)$record->coupon_id] = ($receivedMap[(int)$record->coupon_id] ?? 0) + 1;
            }
        }
        $list = [];
        foreach ($rows as $coupon) {
            /** @var Coupon $coupon */
            $limitReached = (int)$coupon->per_limit > 0
                && $userId > 0
                && ($receivedMap[(int)$coupon->id] ?? 0) >= (int)$coupon->per_limit;
            $soldOut = (int)$coupon->stock > 0 && (int)$coupon->sent_count >= (int)$coupon->stock;
            $list[] = [
                'id' => (int)$coupon->id,
                'name' => $coupon->name,
                'kind' => (int)$coupon->kind,
                'offAmount' => (float)$coupon->off_amount,
                'offPercent' => (int)$coupon->off_percent,
                'minAmount' => (float)$coupon->min_amount,
                'stock' => (int)$coupon->stock,
                'sentCount' => (int)$coupon->sent_count,
                'limitReached' => $limitReached,
                'soldOut' => $soldOut,
            ];
        }
        return $this->ok(['list' => $list]);
    }

    public function actionReceive(): \yii\web\Response
    {
        CouponService::receive(
            $this->userId(),
            $this->tenantId(),
            $this->intInput('id')
        );
        return $this->ok(null, '领取成功');
    }

    /** 我的券包 */
    public function actionMine(): \yii\web\Response
    {
        $query = UserCoupon::find()
            ->where(['user_id' => $this->userId()]);
        $status = $this->intInput('status', 0);
        if ($status > 0) {
            $query->andWhere(['status' => $status]);
        }
        $query->orderBy('id DESC');
        $labels = [1 => '未使用', 2 => '已使用', 3 => '已过期'];
        return $this->okList($query, static function (UserCoupon $record) use ($labels) {
            $coupon = Coupon::find()->where(['id' => $record->coupon_id])->one();
            return [
                'id' => (int)$record->id,
                'status' => (int)$record->status,
                'statusLabel' => $labels[(int)$record->status] ?? '',
                'expireAt' => (int)$record->expire_at,
                'name' => $coupon?->name,
                'kind' => $coupon ? (int)$coupon->kind : 1,
                'offAmount' => $coupon ? (float)$coupon->off_amount : 0,
                'offPercent' => $coupon ? (int)$coupon->off_percent : 100,
                'minAmount' => $coupon ? (float)$coupon->min_amount : 0,
            ];
        });
    }
}
