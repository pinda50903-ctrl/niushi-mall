<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\common\components\ApiException;
use app\common\model\Order;
use app\common\services\GoodsService;
use app\common\services\OrderService;

/**
 * 订单
 */
final class OrderController extends UserAwareController
{
    /** 下单前预览：金额试算（不落库） */
    public function actionPreview(): \yii\web\Response
    {
        $userId = $this->userId();
        $tenantId = $this->tenantId();

        // 简化：复用 submit 的计算逻辑但不开事务不落库
        $cartIds = array_map('intval', (array)($this->input('cartIds') ?? []));
        $goodsId = $this->intInput('goodsId');
        $num = max(1, $this->intInput('num', 1));

        $lines = [];
        if ($goodsId > 0) {
            $lines[] = ['goods_id' => $goodsId, 'sku_id' => $this->intInput('skuId'), 'num' => $num];
        } elseif ($cartIds !== []) {
            $rows = \app\common\model\Cart::find()
                ->where(['and', ['user_id' => $userId], ['id' => $cartIds]])
                ->all();
            foreach ($rows as $row) {
                $lines[] = ['goods_id' => (int)$row->goods_id, 'sku_id' => (int)$row->sku_id, 'num' => (int)$row->num];
            }
        } else {
            $this->fail('请选择商品');
        }

        $items = [];
        $goodsAmount = 0.0;
        foreach ($lines as $line) {
            [$goods, $sku, $price, $stock] = GoodsService::resolveBuyable($tenantId, $line['goods_id'], $line['sku_id']);
            if ($stock < $line['num']) {
                $this->fail('「' . $goods->title . '」库存不足，仅剩 ' . $stock);
            }
            $amount = round($price * $line['num'], 2);
            $goodsAmount += $amount;
            $items[] = [
                'goods_id' => (int)$goods->id,
                'category_id' => (int)$goods->category_id,
                'amount' => $amount,
                'title' => $goods->title,
                'cover' => \app\common\services\UploadService::url((string)$goods->cover),
                'specText' => $sku ? (string)$sku->spec_text : '',
                'num' => $line['num'],
                'price' => $price,
            ];
        }

        // 可用优惠券
        $usableCoupons = \app\common\services\CouponService::usableCoupons($userId, $tenantId, $items);
        $coupons = $usableCoupons === [] ? [] : \app\common\model\UserCoupon::find()
            ->where(['id' => $usableCoupons])
            ->all();

        $trade = \app\common\services\SettingService::get('trade', $tenantId);
        return $this->ok([
            'items' => $items,
            'goodsAmount' => round($goodsAmount, 2),
            'integralRate' => (float)($trade['integral_deduction_rate'] ?? 0),
            'usableCoupons' => array_map(static function ($c) {
                /** @var \app\common\model\UserCoupon $c */
                $coupon = \app\common\model\Coupon::find()->where(['id' => $c->coupon_id])->one();
                return [
                    'id' => (int)$c->id,
                    'name' => $coupon?->name,
                    'kind' => $coupon ? (int)$coupon->kind : 1,
                    'offAmount' => $coupon ? (float)$coupon->off_amount : 0,
                    'offPercent' => $coupon ? (int)$coupon->off_percent : 100,
                    'minAmount' => $coupon ? (float)$coupon->min_amount : 0,
                    'expireAt' => (int)$c->expire_at,
                ];
            }, $coupons),
            'payChannels' => $trade['order_pay_channels'],
        ]);
    }

    public function actionSubmit(): \yii\web\Response
    {
        $order = OrderService::submit(
            $this->userId(),
            $this->tenantId(),
            (string)$this->input('source', 'weapp'),
            $this->body()
        );
        return $this->ok([
            'order' => [
                'id' => (int)$order->id,
                'orderNo' => $order->order_no,
                'payAmount' => (float)$order->pay_amount,
            ],
        ], '下单成功');
    }

    public function actionList(): \yii\web\Response
    {
        $query = Order::find()
            ->where(['user_id' => $this->userId()]);
        if ($this->input('status') !== null && $this->input('status') !== '') {
            $query->andWhere(['status' => (int)$this->input('status')]);
        }
        $query->orderBy('id DESC');
        return $this->okList($query, static fn (Order $o) => OrderService::detail($o));
    }

    public function actionDetail(): \yii\web\Response
    {
        /** @var Order|null $order */
        $order = Order::find()
            ->where(['id' => $this->intInput('id'), 'user_id' => $this->userId()])
            ->one();
        if (!$order) {
            throw new ApiException('订单不存在', ApiException::NOT_FOUND);
        }
        return $this->ok(['order' => OrderService::detail($order)]);
    }

    public function actionCancel(): \yii\web\Response
    {
        /** @var Order|null $order */
        $order = Order::find()
            ->where(['id' => $this->intInput('id'), 'user_id' => $this->userId()])
            ->one();
        if (!$order) {
            throw new ApiException('订单不存在', ApiException::NOT_FOUND);
        }
        OrderService::cancel($order);
        return $this->ok(null, '订单已取消');
    }

    public function actionReceipt(): \yii\web\Response
    {
        /** @var Order|null $order */
        $order = Order::find()
            ->where(['id' => $this->intInput('id'), 'user_id' => $this->userId()])
            ->one();
        if (!$order) {
            throw new ApiException('订单不存在', ApiException::NOT_FOUND);
        }
        OrderService::receipt($order);
        return $this->ok(null, '已确认收货');
    }

    /* ---------- 售后 ---------- */

    public function actionRefundApply(): \yii\web\Response
    {
        $userId = $this->userId();
        $itemId = $this->intInput('itemId');
        /** @var \app\common\model\OrderItem|null $item */
        $item = \app\common\model\OrderItem::find()
            ->where(['id' => $itemId])
            ->one();
        if (!$item) {
            throw new ApiException('订单明细不存在', ApiException::NOT_FOUND);
        }
        /** @var Order|null $order */
        $order = Order::find()->where(['id' => $item->order_id, 'user_id' => $userId])->one();
        if (!$order || !in_array((int)$order->status, [Order::STATUS_PENDING_DELIVERY, Order::STATUS_DELIVERED, Order::STATUS_FINISHED], true)) {
            $this->fail('当前订单状态不支持售后');
        }
        if ((int)$item->refund_state !== 0) {
            $this->fail('该商品已在售后中');
        }
        $refund = new \app\common\model\OrderRefund();
        $refund->tenant_id = $this->tenantId();
        $refund->refund_no = OrderService::makeNo('R');
        $refund->order_id = (int)$order->id;
        $refund->item_id = (int)$item->id;
        $refund->user_id = $userId;
        $refund->type = (int)$this->input('type', 0) === 1 ? 1 : 0;
        $refund->reason = $this->strInput('reason', '不想要了');
        $refund->description = $this->strInput('description');
        $refund->voucher = json_encode((array)($this->input('voucher') ?? []), JSON_UNESCAPED_UNICODE);
        // 金额=明细实付（简化：单价*num，多件整退）
        $refund->amount = (string)((float)$item->unit_price * (int)$item->num);
        $refund->status = 10;
        $refund->save(false);
        $item->refund_state = 10;
        $item->save(false);
        return $this->ok(['refund' => $refund], '售后申请已提交');
    }

    public function actionRefundDetail(): \yii\web\Response
    {
        /** @var \app\common\model\OrderRefund|null $refund */
        $refund = \app\common\model\OrderRefund::find()
            ->where(['id' => $this->intInput('id'), 'user_id' => $this->userId()])
            ->one();
        if (!$refund) {
            throw new ApiException('售后单不存在', ApiException::NOT_FOUND);
        }
        $data = $refund->toArray();
        $data['voucher'] = GoodsService::jsonArray($refund->voucher);
        return $this->ok(['refund' => $data]);
    }
}
