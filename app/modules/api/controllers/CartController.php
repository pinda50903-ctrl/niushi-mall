<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\common\services\CartService;

/**
 * 购物车
 */
final class CartController extends UserAwareController
{
    public function actionIndex(): \yii\web\Response
    {
        $userId = $this->userId();
        $list = CartService::listing($userId, $this->tenantId());
        $valid = array_filter($list, static fn ($row) => !$row['invalid']);
        return $this->ok([
            'list' => array_values($list),
            'count' => array_sum(array_column($valid, 'num')),
            'amount' => round(array_sum(array_map(
                static fn ($row) => $row['price'] * $row['num'],
                $valid
            )), 2),
        ]);
    }

    public function actionAdd(): \yii\web\Response
    {
        \app\common\services\CartService::add(
            $this->userId(),
            $this->tenantId(),
            $this->intInput('goodsId'),
            $this->intInput('skuId'),
            $this->intInput('num', 1)
        );
        return $this->ok(null, '已加入购物车');
    }

    public function actionUpdateNum(): \yii\web\Response
    {
        \app\common\services\CartService::updateNum(
            $this->userId(),
            $this->intInput('id'),
            $this->intInput('num')
        );
        return $this->ok(null, '已更新');
    }

    public function actionRemove(): \yii\web\Response
    {
        \app\common\services\CartService::remove(
            $this->userId(),
            array_map('intval', (array)($this->input('ids') ?? []))
        );
        return $this->ok(null, '已删除');
    }
}
