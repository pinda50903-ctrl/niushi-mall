<?php

declare(strict_types=1);

namespace app\plugins\distribution\controllers;

use app\common\components\ApiException;
use app\common\model\Order;
use app\plugins\PluginRestController;
use app\plugins\distribution\models\Commission;
use app\plugins\distribution\models\Relation;
use app\plugins\distribution\models\Withdraw;
use app\plugins\distribution\services\CommissionService;
use Yii;

/**
 * 分销用户端：中心 / 绑定 / 团队 / 佣金 / 提现
 */
final class CenterController extends PluginRestController
{
    protected function pluginCode(): string
    {
        return 'distribution';
    }

    /** 分销中心概览 */
    public function actionIndex(): \yii\web\Response
    {
        $userId = $this->currentUserId();
        $summary = CommissionService::summary($this->tenantId(), $userId);
        $summary['parentId'] = CommissionService::parentId($this->tenantId(), $userId);
        return $this->ok($summary);
    }

    /** 绑定上级 */
    public function actionBind(): \yii\web\Response
    {
        $userId = $this->currentUserId();
        $parentId = CommissionService::bind($this->tenantId(), $userId, $this->strInput('inviteCode'));
        return $this->ok(['parentId' => $parentId, 'parentInviteCode' => CommissionService::inviteCode($parentId)], '绑定成功');
    }

    /** 我的团队：level 1 直推 / 2 间推 */
    public function actionTeam(): \yii\web\Response
    {
        $userId = $this->currentUserId();
        $level = $this->intInput('level', 1) === 2 ? 2 : 1;
        $tenantId = $this->tenantId();
        if ($level === 1) {
            $parentId = $userId;
        } else {
            $ids = Relation::find()->select('user_id')
                ->where(['tenant_id' => $tenantId, 'parent_id' => $userId])->column();
            if ($ids === []) {
                return $this->ok(['level' => 2, 'total' => 0, 'list' => []]);
            }
            $parentId = array_map('intval', $ids);
        }
        $query = Relation::find()
            ->where(['tenant_id' => $tenantId, 'parent_id' => $parentId])
            ->orderBy('id DESC');
        return $this->okList($query, static function (Relation $relation): array {
            /** @var \app\common\model\User|null $user */
            $user = \app\common\model\User::find()
                ->select('id, nickname, avatar, created_at')
                ->where(['id' => (int)$relation->user_id])->asArray()->one();
            $earned = (float)Commission::find()
                ->where(['user_id' => (int)$relation->user_id, 'status' => Commission::STATUS_SETTLED])
                ->sum('amount');
            return [
                'userId' => (int)$relation->user_id,
                'nickname' => $user['nickname'] ?? '神秘用户',
                'avatar' => isset($user['avatar']) ? \app\common\services\UploadService::url((string)$user['avatar']) : '',
                'bindAt' => (int)$relation->bind_at,
                'earnedAmount' => $earned,
            ];
        });
    }

    /** 佣金明细：pending 待结算 / settled 已入账 */
    public function actionCommissions(): \yii\web\Response
    {
        $userId = $this->currentUserId();
        $status = $this->input('status');
        $query = Commission::find()
            ->where(['tenant_id' => $this->tenantId(), 'user_id' => $userId])
            ->orderBy('id DESC');
        if ($status !== null && $status !== '') {
            $query->andWhere(['status' => (int)$status]);
        }
        return $this->okList($query, static fn (Commission $row): array => [
            'id' => (int)$row->id,
            'orderNo' => (string)$row->order_no,
            'level' => (int)$row->level,
            'amount' => (float)$row->amount,
            'status' => (int)$row->status,
            'statusText' => [0 => '待结算', 1 => '已入账', 2 => '已失效'][(int)$row->status] ?? '未知',
            'settleAt' => (int)$row->settle_at,
            'createdAt' => (int)$row->created_at,
        ]);
    }

    /** 申请提现（佣金余额 → 打款入钱包余额，演示通道） */
    public function actionWithdrawApply(): \yii\web\Response
    {
        $userId = $this->currentUserId();
        $tenantId = $this->tenantId();
        $amount = round((float)$this->input('amount', 0), 2);
        $config = CommissionService::config($tenantId);
        if ($amount < (float)$config['min_withdraw']) {
            throw new ApiException('最低提现金额为 ' . $config['min_withdraw'] . ' 元', ApiException::VALIDATE);
        }
        $settled = (float)Commission::find()
            ->where(['tenant_id' => $tenantId, 'user_id' => $userId, 'status' => Commission::STATUS_SETTLED])
            ->sum('amount');
        $withdrawn = (float)Withdraw::find()
            ->where(['tenant_id' => $tenantId, 'user_id' => $userId])
            ->andWhere(['<>', 'status', Withdraw::STATUS_REFUSED])
            ->sum('amount');
        if ($amount > round($settled - $withdrawn, 2)) {
            throw new ApiException('可提现佣金不足', ApiException::VALIDATE);
        }
        if (Withdraw::find()->where(['tenant_id' => $tenantId, 'user_id' => $userId, 'status' => Withdraw::STATUS_PENDING])->exists()) {
            throw new ApiException('你有一笔提现待审核', ApiException::VALIDATE);
        }
        $withdraw = new Withdraw();
        $withdraw->tenant_id = $tenantId;
        $withdraw->user_id = $userId;
        $withdraw->amount = $amount;
        $withdraw->status = Withdraw::STATUS_PENDING;
        $withdraw->created_at = time();
        $withdraw->save(false);
        return $this->ok(['withdrawId' => (int)$withdraw->id], '提现申请已提交，等待审核');
    }

    /** 提现记录 */
    public function actionWithdrawLogs(): \yii\web\Response
    {
        $userId = $this->currentUserId();
        $query = Withdraw::find()
            ->where(['tenant_id' => $this->tenantId(), 'user_id' => $userId])
            ->orderBy('id DESC');
        return $this->okList($query, static fn (Withdraw $row): array => [
            'id' => (int)$row->id,
            'amount' => (float)$row->amount,
            'status' => (int)$row->status,
            'statusText' => [0 => '待审核', 1 => '已打款', 2 => '已驳回'][(int)$row->status] ?? '未知',
            'remark' => (string)$row->remark,
            'handledAt' => (int)$row->handled_at,
            'createdAt' => (int)$row->created_at,
        ]);
    }

    /** 我推广的订单（佣金视角） */
    public function actionPromotedOrders(): \yii\web\Response
    {
        $userId = $this->currentUserId();
        $orderIds = Commission::find()
            ->select('order_id')
            ->where(['tenant_id' => $this->tenantId(), 'user_id' => $userId])
            ->column();
        $query = Order::find()
            ->where(['id' => array_map('intval', $orderIds) ?: [0]])
            ->orderBy('id DESC');
        return $this->okList($query, static fn (Order $order): array => [
            'id' => (int)$order->id,
            'orderNo' => (string)$order->order_no,
            'payAmount' => (float)$order->pay_amount,
            'status' => (int)$order->status,
            'createdAt' => (int)$order->created_at,
        ]);
    }
}
