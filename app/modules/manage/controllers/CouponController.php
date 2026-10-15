<?php

declare(strict_types=1);

namespace app\modules\manage\controllers;

use app\common\components\ApiException;
use app\common\model\Coupon;
use app\common\model\UserCoupon;
use app\common\model\User;

/**
 * 优惠券
 */
final class CouponController extends TenantAwareController
{
    protected string $permission = 'mall:coupon';

    public function actionIndex(): \yii\web\Response
    {
        $query = Coupon::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('id DESC');
        return $this->okList($query, static function (Coupon $c) {
            $arr = $c->toArray();
            $arr['scope_value'] = \app\common\services\GoodsService::jsonArray($c->scope_value);
            return $arr;
        });
    }

    public function actionSave(): \yii\web\Response
    {
        $id = $this->intInput('id');
        $name = $this->strInput('name');
        if ($name === '') {
            $this->fail('请填写优惠券名称');
        }
        $coupon = $id > 0
            ? Coupon::find()->where(['id' => $id, 'tenant_id' => $this->tenantId()])->one()
            : new Coupon();
        if ($id > 0 && !$coupon) {
            throw new ApiException('优惠券不存在', ApiException::NOT_FOUND);
        }
        /** @var Coupon $coupon */
        $coupon->tenant_id = $this->tenantId();
        $coupon->name = $name;
        $coupon->kind = (int)$this->input('kind', 1) === 2 ? 2 : 1;
        $coupon->scope = in_array((int)$this->input('scope', 1), [1, 2, 3], true) ? (int)$this->input('scope', 1) : 1;
        $coupon->scope_value = json_encode(array_map('intval', (array)($this->input('scopeValue') ?? [])));
        if ((int)$coupon->kind === Coupon::KIND_REDUCE) {
            $coupon->off_amount = (string)max(0, (float)$this->input('offAmount', 0));
            $coupon->off_percent = 100;
        } else {
            $percent = min(99, max(1, (int)$this->input('offPercent', 90)));
            $coupon->off_percent = $percent;
            $coupon->off_amount = (string)max(0, (float)$this->input('offAmount', 0));
        }
        $coupon->min_amount = (string)max(0, (float)$this->input('minAmount', 0));
        $coupon->stock = (int)$this->input('stock', 0);
        $coupon->per_limit = (int)$this->input('perLimit', 1);
        $coupon->expire_kind = (int)$this->input('expireKind', 1) === 2 ? 2 : 1;
        $coupon->valid_from = (int)strtotime((string)$this->input('validFrom', 'now'));
        $coupon->valid_to = (int)strtotime((string)$this->input('validTo', '+7 days'));
        $coupon->valid_days = (int)$this->input('validDays', 7);
        if ($this->input('status') !== null) {
            $coupon->status = (int)$this->input('status') === 1 ? 1 : 0;
        }
        $coupon->save(false);
        return $this->ok(['coupon' => $coupon], '已保存');
    }

    public function actionDelete(): \yii\web\Response
    {
        Coupon::deleteAll(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()]);
        return $this->ok(null, '已删除');
    }

    /** 定向发放给指定用户 */
    public function actionGrant(): \yii\web\Response
    {
        $couponId = $this->intInput('id');
        /** @var Coupon|null $coupon */
        $coupon = Coupon::find()
            ->where(['id' => $couponId, 'tenant_id' => $this->tenantId(), 'status' => 1])
            ->one();
        if (!$coupon) {
            throw new ApiException('优惠券不存在或未启用', ApiException::NOT_FOUND);
        }
        $userIds = array_map('intval', (array)($this->input('userIds') ?? []));
        if ($userIds === []) {
            $this->fail('请选择发放对象');
        }
        $count = 0;
        $now = time();
        foreach ($userIds as $userId) {
            if (!User::find()->where(['id' => $userId, 'tenant_id' => $this->tenantId()])->exists()) {
                continue;
            }
            $record = new UserCoupon();
            $record->tenant_id = $this->tenantId();
            $record->coupon_id = $couponId;
            $record->user_id = $userId;
            $record->status = UserCoupon::STATUS_UNUSED;
            $record->expire_at = (int)$coupon->expire_kind === 2
                ? $now + (int)$coupon->valid_days * 86400
                : (int)$coupon->valid_to;
            $record->received_at = $now;
            $record->save(false);
            $count++;
        }
        Coupon::updateAllCounters(['sent_count' => $count], ['id' => $couponId]);
        return $this->ok(['granted' => $count], '已发放 ' . $count . ' 张');
    }
}
