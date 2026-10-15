<?php

declare(strict_types=1);

namespace app\modules\manage\controllers;

use app\common\components\ApiException;
use app\common\model\Order;
use app\common\model\OrderItem;
use app\common\model\OrderRefund;
use app\common\services\OrderService;
use Yii;

/**
 * 订单与售后
 */
final class OrderController extends TenantAwareController
{
    protected string $permission = 'mall:order';

    public function actionIndex(): \yii\web\Response
    {
        $query = Order::find()->where(['tenant_id' => $this->tenantId()]);
        if ($this->input('status') !== null && $this->input('status') !== '') {
            $query->andWhere(['status' => (int)$this->input('status')]);
        }
        if (($kw = $this->strInput('keyword')) !== '') {
            $query->andWhere(['or', ['like', 'order_no', $kw], ['like', 'receiver_name', $kw], ['like', 'receiver_phone', $kw]]);
        }
        if (($start = (int)$this->input('startTime', 0)) > 0) {
            $query->andWhere(['>=', 'created_at', $start]);
        }
        if (($end = (int)$this->input('endTime', 0)) > 0) {
            $query->andWhere(['<=', 'created_at', $end]);
        }
        $query->orderBy('id DESC');
        return $this->okList($query, static fn (Order $o) => OrderService::detail($o));
    }

    public function actionView(): \yii\web\Response
    {
        return $this->ok(['order' => OrderService::detail($this->find())]);
    }

    /** 发货 */
    public function actionDeliver(): \yii\web\Response
    {
        $order = $this->find();
        OrderService::deliver($order, $this->strInput('expressNo'));
        $this->logOperation(['op' => 'deliver', 'order_id' => $order->id]);
        return $this->ok(null, '已发货');
    }

    /** 商家备注 */
    public function actionRemark(): \yii\web\Response
    {
        $order = $this->find();
        $order->seller_remark = $this->strInput('remark');
        $order->save(false);
        return $this->ok(null, '备注已保存');
    }

    /* ---------- 售后 ---------- */

    public function actionRefunds(): \yii\web\Response
    {
        $query = OrderRefund::find()->where(['tenant_id' => $this->tenantId()]);
        if ($this->input('status') !== null && $this->input('status') !== '') {
            $query->andWhere(['status' => (int)$this->input('status')]);
        }
        $query->orderBy('id DESC');
        $labels = [10 => '申请中', 20 => '同意待退货', 30 => '已拒绝', 40 => '待收退货', 50 => '已退款', 60 => '已关闭'];
        return $this->okList($query, static function (OrderRefund $r) use ($labels) {
            $arr = $r->toArray();
            $arr['status_label'] = $labels[(int)$r->status] ?? '未知';
            $arr['voucher'] = \app\common\services\GoodsService::jsonArray($r->voucher);
            return $arr;
        });
    }

    /** 同意售后 */
    public function actionRefundAgree(): \yii\web\Response
    {
        $refund = $this->findRefund();
        if ((int)$refund->status !== 10) {
            $this->fail('该售后不在申请中状态');
        }
        $refund->status = (int)$refund->type === 1 ? 20 : 50; // 仅退款直接进入待退款
        $refund->handled_at = time();
        $refund->save(false);
        return $this->ok(null, '已同意');
    }

    public function actionRefundRefuse(): \yii\web\Response
    {
        $refund = $this->findRefund();
        if (!in_array((int)$refund->status, [10, 20, 40], true)) {
            $this->fail('当前状态不可拒绝');
        }
        $refund->status = 30;
        $refund->refused_reason = $this->strInput('reason');
        $refund->handled_at = time();
        $refund->save(false);
        $this->logOperation(['op' => 'refund_refuse', 'refund_id' => $refund->id]);
        return $this->ok(null, '已拒绝');
    }

    /** 确认收到退货 */
    public function actionRefundReceive(): \yii\web\Response
    {
        $refund = $this->findRefund();
        if ((int)$refund->status !== 40) {
            $this->fail('该售后不在待收退货状态');
        }
        $refund->status = 50;
        $refund->save(false);
        return $this->ok(null, '已收货，进入退款流程');
    }

    /** 执行退款（真实环境接微信/支付宝退款；此处走余额退回或模拟） */
    public function actionRefundExecute(): \yii\web\Response
    {
        $refund = $this->findRefund();
        if (!in_array((int)$refund->status, [20, 40], true) && (int)$refund->status !== 50) {
            $this->fail('当前状态不可退款');
        }
        $tx = Yii::$app->db->beginTransaction();
        try {
            // 退回余额
            \app\common\services\WalletService::changeBalance(
                (int)$refund->user_id,
                (float)$refund->amount,
                1,
                'refund',
                (string)$refund->refund_no,
                '售后退款'
            );
            $refund->status = 50;
            $refund->refunded_at = time();
            $refund->save(false);

            // 明细与订单
            OrderItem::updateAll(['refund_state' => 20], ['id' => (int)$refund->item_id]);
            $order = Order::find()->where(['id' => (int)$refund->order_id])->one();
            if ($order) {
                // 全部明细退款则关闭订单并返还库存
                $openLeft = (int)Yii::$app->db->createCommand(
                    'SELECT COUNT(*) FROM {{%order_item}} WHERE order_id = :o AND refund_state = 0',
                    [':o' => $order->id]
                )->queryScalar();
                if ($openLeft === 0) {
                    OrderItem::updateAll(['refund_state' => 20], ['order_id' => $order->id]);
                    $items = OrderItem::find()->where(['order_id' => $order->id])->all();
                    foreach ($items as $item) {
                        \app\common\services\GoodsService::incrementStock(
                            (int)$item->goods_id, (int)$item->sku_id, (int)$item->num
                        );
                    }
                    if ((int)$order->coupon_id > 0) {
                        \app\common\model\UserCoupon::updateAll(
                            ['status' => \app\common\model\UserCoupon::STATUS_UNUSED, 'order_id' => 0, 'used_at' => null],
                            ['id' => (int)$order->coupon_id]
                        );
                    }
                    $order->status = Order::STATUS_CLOSED;
                    $order->closed_at = time();
                    $order->save(false);
                }
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
        $this->logOperation(['op' => 'refund_execute', 'refund_id' => $refund->id]);
        return $this->ok(null, '退款完成（已退回用户余额）');
    }

    private function find(): Order
    {
        /** @var Order|null $order */
        $order = Order::find()
            ->where(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()])
            ->one();
        if (!$order) {
            throw new ApiException('订单不存在', ApiException::NOT_FOUND);
        }
        return $order;
    }

    private function findRefund(): OrderRefund
    {
        /** @var OrderRefund|null $refund */
        $refund = OrderRefund::find()
            ->where(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()])
            ->one();
        if (!$refund) {
            throw new ApiException('售后单不存在', ApiException::NOT_FOUND);
        }
        return $refund;
    }
}
