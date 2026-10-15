<?php

declare(strict_types=1);

namespace app\plugins\distribution\services;

use app\common\components\ApiException;
use app\common\model\Order;
use app\common\model\User;
use app\common\services\WalletService;
use app\plugins\distribution\models\Commission;
use app\plugins\distribution\models\Relation;
use Yii;

/**
 * 分销佣金：关系绑定 → 订单支付产生待结算佣金 → 订单完成入账余额
 */
final class CommissionService
{
    /** 默认配置（管理员可在插件配置中覆盖） */
    public static function defaults(): array
    {
        return [
            'first_ratio' => 10,   // 一级佣金比例（%）
            'second_ratio' => 5,   // 二级佣金比例（%）
            'min_withdraw' => 10,  // 最低提现金额
        ];
    }

    public static function config(int $tenantId): array
    {
        $saved = \app\common\services\PluginSettingService::get('distribution', $tenantId);
        return array_merge(self::defaults(), is_array($saved) ? $saved : []);
    }

    /** 用户邀请码（可逆编码，无需落库） */
    public static function inviteCode(int $userId): string
    {
        return str_pad((string)base_convert((string)$userId, 10, 36), 6, '0', STR_PAD_LEFT);
    }

    public static function decodeInvite(string $code): int
    {
        $code = strtolower(trim($code));
        if ($code === '' || !preg_match('/^[0-9a-z]{1,10}$/', $code)) {
            return 0;
        }
        return (int)base_convert($code, 36, 10);
    }

    /** 我的上级 user_id（0 表示未绑定） */
    public static function parentId(int $tenantId, int $userId): int
    {
        $parentId = Relation::find()
            ->select('parent_id')
            ->where(['tenant_id' => $tenantId, 'user_id' => $userId])
            ->scalar();
        return (int)($parentId ?: 0);
    }

    /** 绑定上级（每人仅一次） */
    public static function bind(int $tenantId, int $userId, string $inviteCode): int
    {
        if ($userId <= 0) {
            throw new ApiException('请先登录', ApiException::UNAUTHORIZED);
        }
        $parentId = self::decodeInvite($inviteCode);
        if ($parentId <= 0 || !User::find()->where(['id' => $parentId, 'status' => 1])->exists()) {
            throw new ApiException('邀请码无效', ApiException::VALIDATE);
        }
        if ($parentId === $userId) {
            throw new ApiException('不能绑定自己为上级', ApiException::VALIDATE);
        }
        if (self::parentId($tenantId, $userId) > 0) {
            throw new ApiException('你已绑定上级，无法重复绑定', ApiException::VALIDATE);
        }
        // 防环：parent 的上级不能是我
        if (self::parentId($tenantId, $parentId) === $userId) {
            throw new ApiException('邀请关系形成闭环，无法绑定', ApiException::VALIDATE);
        }
        $relation = new Relation();
        $relation->tenant_id = $tenantId;
        $relation->user_id = $userId;
        $relation->parent_id = $parentId;
        $relation->bind_at = time();
        $relation->created_at = time();
        $relation->save(false);
        return $parentId;
    }

    /**
     * 订单支付成功：按实付金额产生待结算佣金（挂在 order.paid 事件上）
     */
    public static function onOrderPaid(Order $order): void
    {
        $tenantId = (int)$order->tenant_id;
        $buyerId = (int)$order->user_id;
        $payAmount = (float)$order->pay_amount;
        if ($payAmount <= 0 || $buyerId <= 0) {
            return;
        }
        $config = self::config($tenantId);
        $ratios = [
            1 => (float)$config['first_ratio'],
            2 => (float)$config['second_ratio'],
        ];
        $level1 = self::parentId($tenantId, $buyerId);
        if ($level1 <= 0) {
            return;
        }
        $level2 = self::parentId($tenantId, $level1);
        $now = time();
        foreach ([1 => $level1, 2 => $level2] as $level => $beneficiary) {
            if ($beneficiary <= 0 || $ratios[$level] <= 0) {
                continue;
            }
            $exists = Commission::find()
                ->where(['order_id' => (int)$order->id, 'user_id' => $beneficiary, 'level' => $level])
                ->exists();
            if ($exists) {
                continue;
            }
            $row = new Commission();
            $row->tenant_id = $tenantId;
            $row->user_id = $beneficiary;
            $row->buyer_id = $buyerId;
            $row->order_id = (int)$order->id;
            $row->order_no = (string)$order->order_no;
            $row->level = $level;
            $row->amount = round($payAmount * $ratios[$level] / 100, 2);
            $row->status = Commission::STATUS_PENDING;
            $row->created_at = $now;
            $row->save(false);
        }
    }

    /**
     * 订单完成：待结算佣金入账余额（挂在 order.received 事件上）
     */
    public static function onOrderReceived(Order $order): void
    {
        $rows = Commission::find()
            ->where(['order_id' => (int)$order->id, 'status' => Commission::STATUS_PENDING])
            ->all();
        foreach ($rows as $row) {
            /** @var Commission $row */
            $row->status = Commission::STATUS_SETTLED;
            $row->settle_at = time();
            $row->save(false);
            WalletService::changeBalance(
                (int)$row->user_id,
                (float)$row->amount,
                1,
                'distribution_commission',
                (string)$row->order_no,
                '分销佣金入账（' . ($row->level === 1 ? '一级' : '二级') . '）'
            );
        }
    }

    /** 分销中心概览 */
    public static function summary(int $tenantId, int $userId): array
    {
        $level1 = (int)Relation::find()
            ->where(['tenant_id' => $tenantId, 'parent_id' => $userId])->count();
        $level1Ids = Relation::find()
            ->select('user_id')
            ->where(['tenant_id' => $tenantId, 'parent_id' => $userId])
            ->column();
        $level2 = $level1Ids === []
            ? 0
            : (int)Relation::find()
                ->where(['tenant_id' => $tenantId, 'parent_id' => $level1Ids])->count();

        $sums = Commission::find()
            ->select(['status', 'SUM(amount) AS total'])
            ->where(['tenant_id' => $tenantId, 'user_id' => $userId])
            ->groupBy('status')
            ->asArray()->all();
        $byStatus = [];
        foreach ($sums as $s) {
            $byStatus[(int)$s['status']] = round((float)$s['total'], 2);
        }
        /** @var User|null $user */
        $user = User::find()->where(['id' => $userId])->one();

        return [
            'isDistributor' => true,
            'inviteCode' => self::inviteCode($userId),
            'teamLevel1' => $level1,
            'teamLevel2' => $level2,
            'pendingAmount' => $byStatus[Commission::STATUS_PENDING] ?? 0,
            'settledAmount' => $byStatus[Commission::STATUS_SETTLED] ?? 0,
            'totalAmount' => round(($byStatus[Commission::STATUS_PENDING] ?? 0) + ($byStatus[Commission::STATUS_SETTLED] ?? 0), 2),
            'balance' => $user ? (float)$user->balance : 0,
            'config' => self::config($tenantId),
        ];
    }

    /** 注册全局事件监听（由插件 bootstrap 调用） */
    public static function listen(): void
    {
        Yii::$app->on('order.paid', static function (\app\common\events\OrderEvent $e): void {
            $order = $e->order;
            if ($order === null || !\app\common\plugins\PluginManager::tenantHas('distribution', (int)$order->tenant_id)) {
                return;
            }
            self::onOrderPaid($order);
        });
        Yii::$app->on('order.received', static function (\app\common\events\OrderEvent $e): void {
            $order = $e->order;
            if ($order === null || !\app\common\plugins\PluginManager::tenantHas('distribution', (int)$order->tenant_id)) {
                return;
            }
            self::onOrderReceived($order);
        });
    }
}
