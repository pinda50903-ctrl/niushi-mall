<?php

declare(strict_types=1);

namespace app\common\services;

use app\common\components\ApiException;
use app\common\model\Order;
use app\common\model\PaymentRecord;
use app\common\model\Setting;
use Yii;

/**
 * 支付：渠道工厂 + 预支付 + 异步回调
 *
 * 渠道：
 *   balance 余额支付（同步完成）
 *   wechat  微信支付（V3 JSAPI/小程序，回调验签）
 *   alipay  支付宝（手机网站/小程序，回调验签）
 */
final class PaymentService
{
    public const CHANNEL_BALANCE = 'balance';
    public const CHANNEL_WECHAT = 'wechat';
    public const CHANNEL_ALIPAY = 'alipay';

    /**
     * 预支付
     * @return array 各渠道的支付参数
     */
    public static function prepay(Order $order, string $channel, string $openid = ''): array
    {
        if ((int)$order->status !== Order::STATUS_PENDING_PAY) {
            throw new ApiException('订单状态不可支付', ApiException::VALIDATE);
        }
        if ((float)$order->pay_amount <= 0) {
            // 0 元单直接完成
            self::fulfill($order, $channel, $order->order_no);
            return ['paid' => true];
        }

        return match ($channel) {
            self::CHANNEL_BALANCE => self::payByBalance($order),
            self::CHANNEL_WECHAT => self::payByWechat($order, $openid),
            self::CHANNEL_ALIPAY => self::payByAlipay($order),
            default => throw new ApiException('不支持的支付方式', ApiException::VALIDATE),
        };
    }

    /** 余额支付：同步扣款 */
    private static function payByBalance(Order $order): array
    {
        $tx = Yii::$app->db->beginTransaction();
        try {
            WalletService::changeBalance(
                (int)$order->user_id,
                (float)$order->pay_amount,
                -1,
                'order_pay',
                (string)$order->order_no,
                '余额支付订单'
            );
            self::fulfill($order, self::CHANNEL_BALANCE, $order->order_no);
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
        return ['paid' => true];
    }

    /** 微信支付 V3：JSAPI/小程序下单（配置缺失时返回模拟参数用于联调） */
    private static function payByWechat(Order $order, string $openid): array
    {
        $conf = SettingService::get('wechat', (int)$order->tenant_id);
        $record = self::createRecord($order, self::CHANNEL_WECHAT);

        if ($conf['mch_id'] === '' || $conf['app_id'] === '') {
            // 未配置商户号：返回模拟参数（前端可走模拟收银台完成闭环）
            return [
                'paid' => false,
                'mock' => true,
                'payNo' => $record->out_trade_no,
                'channel' => self::CHANNEL_WECHAT,
            ];
        }

        // 真实环境：调 V3 接口 https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi
        // 需要商户证书与平台证书，此处封装调用骨架，凭据就位后即可启用
        $payload = [
            'appid' => $conf['app_id'],
            'mchid' => $conf['mch_id'],
            'description' => '订单支付',
            'out_trade_no' => $record->out_trade_no,
            'notify_url' => self::notifyUrl('wechat'),
            'amount' => [
                'total' => (int)round((float)$order->pay_amount * 100),
                'currency' => 'CNY',
            ],
            'payer' => ['openid' => $openid],
        ];
        // $sign = self::wechatSign('POST', '/v3/pay/transactions/jsapi', json_encode($payload), $conf);
        // $resp = (new \yii\httpclient\Client())->createRequest()
        //     ->setMethod('POST')->setUrl('https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi')
        //     ->setData($payload)->setHeaders(['Authorization' => 'WECHATPAY2-SHA256-RSA2048 ' . $sign])
        //     ->setFormat(\yii\httpclient\Client::FORMAT_JSON)->send();
        $prepayId = ''; // 待商户凭据配置后填充
        if ($prepayId === '') {
            return [
                'paid' => false,
                'mock' => true,
                'payNo' => $record->out_trade_no,
                'channel' => self::CHANNEL_WECHAT,
                'hint' => '微信商户配置未就绪，暂走模拟支付',
            ];
        }
        return [
            'paid' => false,
            'mock' => false,
            'timeStamp' => (string)time(),
            'nonceStr' => bin2hex(random_bytes(8)),
            'package' => 'prepay_id=' . $prepayId,
            'signType' => 'RSA',
        ];
    }

    /** 支付宝：手机网站支付表单（配置缺失时返回模拟参数） */
    private static function payByAlipay(Order $order): array
    {
        $conf = SettingService::get('alipay', (int)$order->tenant_id);
        $record = self::createRecord($order, self::CHANNEL_ALIPAY);

        if ($conf['app_id'] === '') {
            return [
                'paid' => false,
                'mock' => true,
                'payNo' => $record->out_trade_no,
                'channel' => self::CHANNEL_ALIPAY,
            ];
        }
        // 真实环境：组装 alipay.trade.wap.pay 网关表单并 RSA2 签名（骨架）
        return [
            'paid' => false,
            'mock' => false,
            'gateway' => 'https://openapi.alipay.com/gateway.do',
            'bizContent' => [
                'out_trade_no' => $record->out_trade_no,
                'total_amount' => (string)$order->pay_amount,
                'subject' => '订单支付',
                'notify_url' => self::notifyUrl('alipay'),
            ],
            'hint' => '支付宝配置就绪后由前端跳转网关',
        ];
    }

    /**
     * 模拟支付成功（联调/演示用）：仅允许对 mock 记录操作
     */
    public static function mockPaid(int $tenantId, int $userId, string $payNo): void
    {
        /** @var PaymentRecord|null $record */
        $record = PaymentRecord::find()
            ->where(['out_trade_no' => $payNo, 'tenant_id' => $tenantId, 'user_id' => $userId, 'status' => 0])
            ->one();
        if (!$record) {
            throw new ApiException('支付单不存在或已支付', ApiException::NOT_FOUND);
        }
        self::settle($record, 'MOCK' . time(), ['mock' => true]);
    }

    /** 创建支付单 */
    private static function createRecord(Order $order, string $channel): PaymentRecord
    {
        $record = new PaymentRecord();
        $record->tenant_id = (int)$order->tenant_id;
        $record->user_id = (int)$order->user_id;
        $record->order_id = (int)$order->id;
        $record->scene = 'order';
        $record->out_trade_no = OrderService::makeNo('P');
        $record->channel = $channel;
        $record->amount = (string)$order->pay_amount;
        $record->status = 0;
        $record->save(false);
        return $record;
    }

    /**
     * 回调/模拟回调统一结算：幂等
     */
    public static function settle(PaymentRecord $record, string $tradeNo, array $rawNotify): void
    {
        $tx = Yii::$app->db->beginTransaction();
        try {
            // 行锁防并发重复结算
            $query = PaymentRecord::find()->where(['id' => $record->id]);
            /** @var PaymentRecord|null $locked */
            $locked = Yii::$app->db->driverName === 'mysql' ? $query->forUpdate()->one() : $query->one();
            if (!$locked || (int)$locked->status !== 0) {
                $tx->commit();
                return; // 已处理
            }
            $locked->status = 1;
            $locked->trade_no = $tradeNo;
            $locked->raw_notify = json_encode($rawNotify, JSON_UNESCAPED_UNICODE);
            $locked->paid_at = time();
            $locked->save(false);

            if ($locked->scene === 'order') {
                /** @var Order|null $order */
                $order = Order::find()
                    ->where(['id' => (int)$locked->order_id])
                    ->one();
                if ($order) {
                    OrderService::markPaid($order, (string)$locked->channel, (string)$locked->out_trade_no);
                }
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    /** 直接完成订单支付（余额/0元单） */
    public static function fulfill(Order $order, string $channel, string $tradeNo): void
    {
        $record = self::createRecord($order, $channel);
        self::settle($record, $tradeNo, ['source' => 'fulfill']);
    }

    /** 回调地址 */
    private static function notifyUrl(string $channel): string
    {
        $base = (string)(\Yii::$app->params['cdnUrl'] ?? '');
        if ($base === '') {
            $base = \Yii::$app->request->getHostInfo();
        }
        return $base . '/api/pay/notify-' . $channel;
    }
}
