<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\common\components\ApiException;
use app\common\model\Order;
use app\common\model\PaymentRecord;
use app\common\services\PaymentService;
use Yii;

/**
 * 支付：预支付 / 模拟支付 / 渠道回调
 */
final class PayController extends UserAwareController
{
    public $enableCsrfValidation = false;

    /** 预支付 */
    public function actionPrepay(): \yii\web\Response
    {
        $userId = $this->userId();
        /** @var Order|null $order */
        $order = Order::find()
            ->where(['id' => $this->intInput('orderId'), 'user_id' => $userId])
            ->one();
        if (!$order) {
            throw new ApiException('订单不存在', ApiException::NOT_FOUND);
        }
        $channel = (string)$this->input('channel', 'wechat');
        $result = PaymentService::prepay($order, $channel, $this->strInput('openid'));
        return $this->ok($result + ['orderId' => (int)$order->id, 'channel' => $channel]);
    }

    /** 模拟支付（商户配置未就绪时的联调通道） */
    public function actionMock(): \yii\web\Response
    {
        $userId = $this->userId();
        $payNo = $this->strInput('payNo');
        if ($payNo === '') {
            // 直接按订单号找支付单
            /** @var Order|null $order */
            $order = Order::find()
                ->where(['order_no' => $this->strInput('orderNo'), 'user_id' => $userId])
                ->one();
            if (!$order) {
                throw new ApiException('订单不存在', ApiException::NOT_FOUND);
            }
            /** @var PaymentRecord|null $record */
            $record = PaymentRecord::find()
                ->where(['order_id' => $order->id, 'status' => 0])
                ->orderBy('id DESC')->one();
            if (!$record) {
                $this->fail('无待支付支付单');
            }
            $payNo = (string)$record->out_trade_no;
        }
        PaymentService::mockPaid($this->tenantId(), $userId, $payNo);
        return $this->ok(null, '支付成功');
    }

    /** 支付结果查询 */
    public function actionStatus(): \yii\web\Response
    {
        /** @var Order|null $order */
        $order = Order::find()
            ->where(['id' => $this->intInput('orderId'), 'user_id' => $this->userId()])
            ->one();
        if (!$order) {
            throw new ApiException('订单不存在', ApiException::NOT_FOUND);
        }
        return $this->ok([
            'status' => (int)$order->status,
            'paid' => (int)$order->status >= Order::STATUS_PENDING_DELIVERY,
        ]);
    }

    /* ---------- 渠道异步回调 ---------- */

    public function actionNotifyWechat(): \yii\web\Response
    {
        // 微信 V3 回调：验签 + 解密 resource.ciphertext（凭据就绪后启用完整实现）
        $raw = Yii::$app->request->getRawBody();
        $data = json_decode((string)$raw, true) ?: [];
        // TODO: 验证 Wechatpay-Signature 头，AES-256-GCM 解密 resource
        $outTradeNo = (string)($data['out_trade_no'] ?? ($data['resource']['ciphertext'] ?? ''));
        /** @var PaymentRecord|null $record */
        $record = $outTradeNo !== ''
            ? PaymentRecord::find()->where(['out_trade_no' => $outTradeNo, 'status' => 0])->one()
            : null;
        if ($record) {
            \app\common\services\PaymentService::settle($record, (string)($data['transaction_id'] ?? ''), $data);
        }
        return $this->ok(['code' => 'SUCCESS', 'message' => '成功']);
    }

    public function actionNotifyAlipay(): \yii\web\Response
    {
        // 支付宝异步通知：RSA2 验签（凭据就绪后启用完整实现）
        $post = Yii::$app->request->post();
        $outTradeNo = (string)($post['out_trade_no'] ?? '');
        /** @var PaymentRecord|null $record */
        $record = $outTradeNo !== ''
            ? PaymentRecord::find()->where(['out_trade_no' => $outTradeNo, 'status' => 0])->one()
            : null;
        if ($record && ($post['trade_status'] ?? '') === 'TRADE_SUCCESS') {
            \app\common\services\PaymentService::settle($record, (string)($post['trade_no'] ?? ''), $post);
        }
        \Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        \Yii::$app->response->content = 'success';
        return \Yii::$app->response;
    }
}
