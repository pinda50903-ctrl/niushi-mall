<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\common\components\ApiException;
use app\common\model\BalanceLog;
use app\common\model\IntegralLog;
use app\common\model\Order;
use app\common\model\UserAddress;
use app\common\model\UserCoupon;
use app\common\services\CartService;

/**
 * 个人中心 / 地址簿
 */
final class UserController extends UserAwareController
{
    public function actionCenter(): \yii\web\Response
    {
        $userId = $this->userId();
        /** @var \app\common\model\User $user */
        $user = \app\common\model\User::find()->where(['id' => $userId])->one();
        return $this->ok([
            'profile' => [
                'id' => (int)$user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'mobile' => $user->mobile,
                'balance' => (float)$user->balance,
                'integral' => (int)$user->integral,
                'level' => (int)$user->level,
            ],
            'orderTabs' => [
                'pendingPay' => (int)Order::find()->where(['user_id' => $userId, 'status' => Order::STATUS_PENDING_PAY])->count(),
                'pendingDelivery' => (int)Order::find()->where(['user_id' => $userId, 'status' => Order::STATUS_PENDING_DELIVERY])->count(),
                'delivered' => (int)Order::find()->where(['user_id' => $userId, 'status' => Order::STATUS_DELIVERED])->count(),
                'refund' => (int)\app\common\model\OrderRefund::find()
                    ->where(['user_id' => $userId])->andWhere(['in', 'status', [10, 20, 40]])->count(),
            ],
            'cartCount' => CartService::count($userId),
            'couponCount' => (int)UserCoupon::find()->where(['user_id' => $userId, 'status' => UserCoupon::STATUS_UNUSED])->count(),
        ]);
    }

    /* ---------- 地址簿 ---------- */

    public function actionAddresses(): \yii\web\Response
    {
        $rows = UserAddress::find()
            ->where(['user_id' => $this->userId()])
            ->orderBy('is_default DESC, id DESC')
            ->all();
        return $this->ok(['list' => $rows]);
    }

    public function actionSaveAddress(): \yii\web\Response
    {
        $userId = $this->userId();
        $id = $this->intInput('id');
        $receiver = $this->strInput('receiver');
        $phone = $this->strInput('phone');
        $address = $this->strInput('address');
        if ($receiver === '' || $phone === '' || $address === '') {
            $this->fail('请完整填写收货人、电话与地址');
        }
        $addr = $id > 0
            ? UserAddress::find()->where(['id' => $id, 'user_id' => $userId])->one()
            : new UserAddress();
        if ($id > 0 && !$addr) {
            throw new ApiException('地址不存在', ApiException::NOT_FOUND);
        }
        /** @var UserAddress $addr */
        $addr->tenant_id = $this->tenantId();
        $addr->user_id = $userId;
        $addr->receiver = $receiver;
        $addr->phone = $phone;
        $addr->region = $this->strInput('region');
        $addr->address = $address;
        $isDefault = (int)$this->input('isDefault', 0) === 1;
        if ($isDefault) {
            UserAddress::updateAll(['is_default' => 0], ['user_id' => $userId]);
        }
        $addr->is_default = $isDefault ? 1 : 0;
        $addr->save(false);
        return $this->ok(['address' => $addr], '已保存');
    }

    public function actionDeleteAddress(): \yii\web\Response
    {
        UserAddress::deleteAll(['id' => $this->intInput('id'), 'user_id' => $this->userId()]);
        return $this->ok(null, '已删除');
    }

    /* ---------- 资金流水 ---------- */

    public function actionBalanceLogs(): \yii\web\Response
    {
        $query = BalanceLog::find()
            ->where(['user_id' => $this->userId()])
            ->orderBy('id DESC');
        return $this->okList($query);
    }

    public function actionIntegralLogs(): \yii\web\Response
    {
        $query = IntegralLog::find()
            ->where(['user_id' => $this->userId()])
            ->orderBy('id DESC');
        return $this->okList($query);
    }
}
