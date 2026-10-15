<?php

declare(strict_types=1);

namespace app\modules\manage\controllers;

use app\common\components\ApiException;
use app\common\model\User;
use app\common\services\WalletService;

/**
 * 用户管理
 */
final class UserController extends TenantAwareController
{
    protected string $permission = 'mall:user';

    public function actionIndex(): \yii\web\Response
    {
        $query = User::find()->where(['tenant_id' => $this->tenantId()]);
        if (($kw = $this->strInput('keyword')) !== '') {
            $query->andWhere(['or', ['like', 'nickname', $kw], ['like', 'mobile', $kw], ['id' => ctype_digit($kw) ? (int)$kw : -1]]);
        }
        $query->orderBy('id DESC');
        return $this->okList($query);
    }

    public function actionView(): \yii\web\Response
    {
        $user = $this->find();
        $data = $user->toArray();
        $data['order_count'] = (int)\app\common\model\Order::find()
            ->where(['user_id' => $user->id])
            ->count();
        $data['balance_logs'] = \app\common\model\BalanceLog::find()
            ->where(['user_id' => $user->id])
            ->orderBy('id DESC')->limit(20)->all();
        $data['integral_logs'] = \app\common\model\IntegralLog::find()
            ->where(['user_id' => $user->id])
            ->orderBy('id DESC')->limit(20)->all();
        return $this->ok(['user' => $data]);
    }

    public function actionSetRemark(): \yii\web\Response
    {
        $user = $this->find();
        $user->remark = $this->strInput('remark');
        $user->save(false);
        return $this->ok(null, '已保存');
    }

    /** 余额调整（正数增加负数扣减） */
    public function actionAdjustBalance(): \yii\web\Response
    {
        $user = $this->find();
        $amount = abs((float)$this->input('amount', 0));
        if ($amount <= 0) {
            $this->fail('请输入调整金额');
        }
        WalletService::changeBalance(
            (int)$user->id,
            $amount,
            (float)$this->input('amount', 0) > 0 ? 1 : -1,
            'admin_adjust',
            '',
            '管理员调整'
        );
        $this->logOperation(['op' => 'adjust_balance', 'user_id' => $user->id, 'amount' => $amount]);
        return $this->ok(null, '余额已调整');
    }

    public function actionAdjustIntegral(): \yii\web\Response
    {
        $user = $this->find();
        $amount = (int)$this->input('amount', 0);
        if ($amount === 0) {
            $this->fail('请输入调整积分');
        }
        WalletService::changeIntegral(
            (int)$user->id,
            abs($amount),
            $amount > 0 ? 1 : -1,
            'admin_adjust',
            '',
            '管理员调整'
        );
        $this->logOperation(['op' => 'adjust_integral', 'user_id' => $user->id, 'amount' => $amount]);
        return $this->ok(null, '积分已调整');
    }

    public function actionToggle(): \yii\web\Response
    {
        $user = $this->find();
        $user->status = (int)$user->status === 1 ? 0 : 1;
        $user->save(false);
        return $this->ok(['status' => (int)$user->status], '已更新');
    }

    private function find(): User
    {
        /** @var User|null $user */
        $user = User::find()
            ->where(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()])
            ->one();
        if (!$user) {
            throw new ApiException('用户不存在', ApiException::NOT_FOUND);
        }
        return $user;
    }
}
