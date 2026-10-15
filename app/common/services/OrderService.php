<?php

declare(strict_types=1);

namespace app\common\services;

use app\common\components\ApiException;
use app\common\model\Cart;
use app\common\model\Coupon;
use app\common\model\FreightTemplate;
use app\common\model\Goods;
use app\common\model\Order;
use app\common\model\OrderItem;
use app\common\model\User;
use app\common\model\UserAddress;
use app\common\model\UserCoupon;
use Yii;

/**
 * 订单：下单、取消、发货、收货、超时关闭
 */
final class OrderService
{
    /**
     * 提交订单
     * @param array $input goodsId/skuId/num 直购 或 cartIds[] 购物车
     */
    public static function submit(int $userId, int $tenantId, string $source, array $input): Order
    {
        $directGoodsId = (int)($input['goodsId'] ?? 0);
        if ($directGoodsId > 0) {
            $cartIds = [];
            $lines = [[
                'goods_id' => $directGoodsId,
                'sku_id' => (int)($input['skuId'] ?? 0),
                'num' => max(1, (int)($input['num'] ?? 1)),
            ]];
        } else {
            $cartIds = array_map('intval', (array)($input['cartIds'] ?? []));
            if ($cartIds === []) {
                throw new ApiException('请选择要结算的商品', ApiException::VALIDATE);
            }
            $lines = [];
            /** @var Cart[] $rows */
            $rows = Cart::find()
                ->where(['and', ['user_id' => $userId], ['id' => $cartIds]])
                ->all();
            foreach ($rows as $row) {
                $lines[] = [
                    'goods_id' => (int)$row->goods_id,
                    'sku_id' => (int)$row->sku_id,
                    'num' => (int)$row->num,
                    'cart_id' => (int)$row->id,
                ];
            }
            if (count($lines) !== count($cartIds)) {
                throw new ApiException('部分商品已失效，请重新结算', ApiException::VALIDATE);
            }
        }

        // 装配明细并校验库存
        $items = [];
        $goodsAmount = 0.0;
        $totalWeight = 0.0;
        $anyFreeShipping = false;
        $allFreeShipping = true;
        foreach ($lines as $line) {
            [$goods, $sku, $price, $stock] = GoodsService::resolveBuyable($tenantId, $line['goods_id'], $line['sku_id']);
            if ($stock < $line['num']) {
                throw new ApiException('「' . $goods->title . '」库存不足，仅剩 ' . $stock, ApiException::VALIDATE);
            }
            $amount = round($price * $line['num'], 2);
            $goodsAmount += $amount;
            $totalWeight += (float)$goods->weight * $line['num'];
            if ((int)$goods->is_free_shipping === 1) {
                $anyFreeShipping = true;
            } else {
                $allFreeShipping = false;
            }
            $items[] = [
                'goods' => $goods,
                'sku' => $sku,
                'num' => $line['num'],
                'goods_id' => (int)$goods->id,
                'sku_id' => $sku ? (int)$sku->id : 0,
                'unit_price' => $price,
                'amount' => $amount,
                'category_id' => (int)$goods->category_id,
                'cart_id' => $line['cart_id'] ?? 0,
            ];
        }
        $goodsAmount = round($goodsAmount, 2);

        // 收货信息
        $deliveryType = (int)($input['deliveryType'] ?? 0);
        if ($deliveryType === 0) {
            $addressId = (int)($input['addressId'] ?? 0);
            /** @var UserAddress|null $address */
            $address = UserAddress::find()
                ->where(['id' => $addressId, 'user_id' => $userId])
                ->one();
            if (!$address) {
                throw new ApiException('请选择收货地址', ApiException::VALIDATE);
            }
        } else {
            $address = null;
        }

        // 优惠券
        $couponDeduction = 0.0;
        $couponRecord = null;
        $inputCouponId = (int)($input['userCouponId'] ?? 0);
        if ($inputCouponId > 0) {
            /** @var UserCoupon|null $couponRecord */
            $couponRecord = UserCoupon::find()
                ->where(['id' => $inputCouponId, 'user_id' => $userId])
                ->one();
            if (!$couponRecord) {
                throw new ApiException('优惠券不存在', ApiException::NOT_FOUND);
            }
            /** @var Coupon|null $coupon */
            $coupon = Coupon::find()->where(['id' => $couponRecord->coupon_id])->one();
            if (!$coupon) {
                throw new ApiException('优惠券信息异常', ApiException::NOT_FOUND);
            }
            $couponDeduction = CouponService::apply($couponRecord, $coupon, $items, $goodsAmount);
        }

        // 积分抵扣
        $trade = SettingService::get('trade', $tenantId);
        $rate = (float)($trade['integral_deduction_rate'] ?? 0); // 每 100 积分抵扣金额
        $integralUsed = 0;
        $integralDeduction = 0.0;
        if ($rate > 0) {
            /** @var User|null $buyer */
            $buyer = User::find()->where(['id' => $userId])->one();
            $maxIntegral = $buyer ? (int)floor((float)$buyer->integral) : 0;
            $ask = (int)($input['integral'] ?? 0);
            $integralUsed = min($ask, $maxIntegral);
            $integralDeduction = round(intdiv($integralUsed, 100) * $rate, 2);
            $integralDeduction = min($integralDeduction, max(0, $goodsAmount - $couponDeduction));
        }

        // 运费
        $freight = self::calcFreight($tenantId, $goodsAmount, count($items), $totalWeight, $anyFreeShipping, $allFreeShipping);

        $payAmount = round($goodsAmount - $couponDeduction - $integralDeduction + $freight, 2);
        if ($payAmount < 0) {
            $payAmount = 0;
        }

        // 落库
        $tx = Yii::$app->db->beginTransaction();
        try {
            $order = new Order();
            $order->tenant_id = $tenantId;
            $order->order_no = self::makeNo('M');
            $order->user_id = $userId;
            $order->status = Order::STATUS_PENDING_PAY;
            $order->goods_amount = $goodsAmount;
            $order->freight_amount = $freight;
            $order->deduction_amount = round($couponDeduction + $integralDeduction, 2);
            $order->pay_amount = $payAmount;
            $order->coupon_id = $couponRecord ? (int)$couponRecord->id : 0;
            $order->integral_deduction = $integralUsed;
            $order->delivery_type = $deliveryType;
            $order->source = $source;
            $order->buyer_message = mb_substr((string)($input['buyerMessage'] ?? ''), 0, 255);
            if ($address) {
                $order->receiver_name = $address->receiver;
                $order->receiver_phone = $address->phone;
                $order->receiver_region = $address->region;
                $order->receiver_address = $address->address;
            }
            if (!$order->save(false)) {
                throw new ApiException('订单保存失败', ApiException::SERVER_ERROR);
            }

            foreach ($items as $item) {
                $row = new OrderItem();
                $row->tenant_id = $tenantId;
                $row->order_id = (int)$order->id;
                $row->goods_id = $item['goods_id'];
                $row->sku_id = $item['sku_id'];
                $row->title = $item['goods']->title;
                $row->cover = (string)$item['goods']->cover;
                $row->spec_text = $item['sku'] ? (string)$item['sku']->spec_text : '';
                $row->unit_price = $item['unit_price'];
                $row->num = $item['num'];
                $row->total_amount = $item['amount'];
                $row->save(false);

                GoodsService::decrementStock($item['goods_id'], $item['sku_id'], $item['num']);
                Goods::updateAllCounters(['sold_count' => $item['num']], ['id' => $item['goods_id']]);

                if ($item['cart_id'] > 0) {
                    Cart::deleteAll(['id' => $item['cart_id']]);
                }
            }

            // 锁券
            if ($couponRecord) {
                $couponRecord->status = UserCoupon::STATUS_USED;
                $couponRecord->order_id = (int)$order->id;
                $couponRecord->used_at = time();
                $couponRecord->save(false);
            }
            // 扣积分
            if ($integralUsed > 0) {
                WalletService::changeIntegral($userId, $integralUsed, -1, 'order_deduct', (string)$order->order_no, '下单积分抵扣');
            }

            $tx->commit();
            return $order;
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    /** 用户取消（待付款） */
    public static function cancel(Order $order): void
    {
        if ((int)$order->status !== Order::STATUS_PENDING_PAY) {
            throw new ApiException('当前状态不可取消', ApiException::VALIDATE);
        }
        $tx = Yii::$app->db->beginTransaction();
        try {
            self::restockAndRelease($order);
            $order->status = Order::STATUS_CLOSED;
            $order->closed_at = time();
            $order->save(false);
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    /** 管理端发货 */
    public static function deliver(Order $order, string $expressNo = ''): void
    {
        if ((int)$order->status !== Order::STATUS_PENDING_DELIVERY) {
            throw new ApiException('订单不是待发货状态', ApiException::VALIDATE);
        }
        if ((int)$order->delivery_type === 0 && $expressNo === '') {
            throw new ApiException('请填写快递单号', ApiException::VALIDATE);
        }
        $order->status = Order::STATUS_DELIVERED;
        $order->delivered_at = time();
        $order->seller_remark = $expressNo !== '' ? '快递单号：' . $expressNo : $order->seller_remark;
        $order->save(false);
    }

    /** 用户确认收货 */
    public static function receipt(Order $order): void
    {
        if ((int)$order->status !== Order::STATUS_DELIVERED) {
            throw new ApiException('订单不是待收货状态', ApiException::VALIDATE);
        }
        $order->status = Order::STATUS_FINISHED;
        $order->finished_at = time();
        $order->save(false);
        Yii::$app->trigger('order.received', new \app\common\events\OrderEvent($order));
    }

    /** 支付成功回调（由 PaymentService 调用，须在事务中） */
    public static function markPaid(Order $order, string $channel, string $outTradeNo): void
    {
        if ((int)$order->status !== Order::STATUS_PENDING_PAY) {
            return; // 幂等
        }
        $order->status = Order::STATUS_PENDING_DELIVERY;
        $order->pay_channel = $channel;
        $order->paid_at = time();
        $order->save(false);
        Yii::$app->trigger('order.paid', new \app\common\events\OrderEvent($order));
    }

    /** 关闭超时未支付订单（控制台定时任务） */
    public static function closeUnpaid(int $minutes = 0): int
    {
        $minutes = $minutes > 0
            ? $minutes
            : max(5, (int)(SettingService::get('trade', 1)['auto_close_minutes'] ?? 30));
        $deadline = time() - $minutes * 60;
        $orders = Order::find()
            ->where(['status' => Order::STATUS_PENDING_PAY])
            ->andWhere(['<', 'created_at', $deadline])
            ->limit(200)
            ->all();
        $closed = 0;
        foreach ($orders as $order) {
            try {
                self::cancel($order);
                $closed++;
            } catch (\Throwable $e) {
                Yii::warning('关闭订单失败 #' . $order->id . ': ' . $e->getMessage(), __METHOD__);
            }
        }
        return $closed;
    }

    /** 订单详情装配 */
    public static function detail(Order $order): array
    {
        $data = $order->toArray();
        $data['items'] = array_map(static function (OrderItem $item) {
            $arr = $item->toArray();
            $arr['cover'] = UploadService::url((string)$item->cover);
            return $arr;
        }, OrderItem::find()->where(['order_id' => $order->id])->all());
        return $data;
    }

    private static function restockAndRelease(Order $order): void
    {
        /** @var OrderItem[] $items */
        $items = OrderItem::find()->where(['order_id' => $order->id])->all();
        foreach ($items as $item) {
            GoodsService::incrementStock((int)$item->goods_id, (int)$item->sku_id, (int)$item->num);
            Goods::updateAllCounters(['sold_count' => -(int)$item->num], ['id' => $item->goods_id]);
        }
        if ((int)$order->coupon_id > 0) {
            UserCoupon::updateAll(
                ['status' => UserCoupon::STATUS_UNUSED, 'order_id' => 0, 'used_at' => null],
                ['id' => (int)$order->coupon_id]
            );
        }
        if ((int)$order->integral_deduction > 0) {
            WalletService::changeIntegral(
                (int)$order->user_id,
                (int)$order->integral_deduction,
                1,
                'order_cancel_return',
                (string)$order->order_no,
                '取消订单返还积分'
            );
        }
    }

    private static function calcFreight(int $tenantId, float $goodsAmount, int $pieceCount, float $weight, bool $anyFree, bool $allFree): float
    {
        if ($allFree) {
            return 0.0;
        }
        $templateId = (int)(SettingService::get('trade', $tenantId)['freight_template_id'] ?? 0);
        if ($templateId <= 0) {
            return 0.0;
        }
        /** @var FreightTemplate|null $tpl */
        $tpl = FreightTemplate::find()
            ->where(['id' => $templateId, 'tenant_id' => $tenantId])
            ->one();
        if (!$tpl) {
            return 0.0;
        }
        if ((float)$tpl->free_order_amount > 0 && $goodsAmount >= (float)$tpl->free_order_amount) {
            return 0.0;
        }
        $base = (float)$tpl->base_amount;
        $baseQ = max(0.0001, (float)$tpl->base_quantity);
        $step = (float)$tpl->step_amount;
        $stepQ = max(0.0001, (float)$tpl->step_quantity);
        $quantity = (int)$tpl->charge_type === 1 ? $weight : $pieceCount;
        if ($quantity <= $baseQ) {
            return $base;
        }
        $extra = ceil(($quantity - $baseQ) / $stepQ);
        // 部分包邮：仅对非包邮商品计续量，简化为按比例
        if ($anyFree) {
            $extra = $extra / 2;
        }
        return round($base + $extra * $step, 2);
    }

    public static function makeNo(string $prefix = 'M'): string
    {
        return $prefix . date('YmdHis') . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
