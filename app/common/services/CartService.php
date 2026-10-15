<?php

declare(strict_types=1);

namespace app\common\services;

use app\common\components\ApiException;
use app\common\model\Cart;
use app\common\model\Goods;
use app\common\model\GoodsSku;
use Yii;

/**
 * 购物车
 */
final class CartService
{
    public static function add(int $userId, int $tenantId, int $goodsId, int $skuId, int $num): void
    {
        $num = max(1, $num);
        [$goods, $sku, , $stock] = GoodsService::resolveBuyable($tenantId, $goodsId, $skuId);

        $row = Cart::find()
            ->where(['user_id' => $userId, 'goods_id' => $goodsId, 'sku_id' => $sku ? $sku->id : 0])
            ->one();
        $newNum = ($row ? (int)$row->num : 0) + $num;
        if ($newNum > $stock) {
            throw new ApiException('库存不足，当前可购 ' . $stock, ApiException::VALIDATE);
        }
        if ($row) {
            $row->num = $newNum;
            $row->save(false);
        } else {
            $row = new Cart();
            $row->tenant_id = $tenantId;
            $row->user_id = $userId;
            $row->goods_id = $goodsId;
            $row->sku_id = $sku ? $sku->id : 0;
            $row->num = $num;
            $row->save(false);
        }
    }

    public static function updateNum(int $userId, int $cartId, int $num): void
    {
        $row = self::own($userId, $cartId);
        if ($num <= 0) {
            $row->delete();
            return;
        }
        [, , , $stock] = GoodsService::resolveBuyable(
            (int)$row->tenant_id,
            (int)$row->goods_id,
            (int)$row->sku_id
        );
        if ($num > $stock) {
            throw new ApiException('库存不足，当前可购 ' . $stock, ApiException::VALIDATE);
        }
        $row->num = $num;
        $row->save(false);
    }

    public static function remove(int $userId, array $cartIds): void
    {
        if ($cartIds === []) {
            return;
        }
        Cart::deleteAll(['and', ['user_id' => $userId], ['id' => $cartIds]]);
    }

    /** 全部清空（下单成功后） */
    public static function clearSelected(int $userId, array $cartIds): void
    {
        self::remove($userId, $cartIds);
    }

    public static function count(int $userId): int
    {
        return (int)Cart::find()->where(['user_id' => $userId])->sum('num');
    }

    /** 购物车列表（带商品实时信息与失效标记） */
    public static function listing(int $userId, int $tenantId): array
    {
        $rows = Cart::find()
            ->where(['user_id' => $userId])
            ->orderBy('id DESC')
            ->all();
        $list = [];
        foreach ($rows as $row) {
            /** @var Goods|null $goods */
            $goods = Goods::find()->where(['id' => $row->goods_id])->one();
            $sku = $row->sku_id > 0 ? GoodsSku::find()->where(['id' => $row->sku_id])->one() : null;
            $available = $goods
                && (int)$goods->status === Goods::STATUS_ON
                && (int)$goods->tenant_id === $tenantId;
            $price = $sku ? (float)$sku->price : ($goods ? (float)$goods->price : 0);
            $stock = $sku ? (int)$sku->stock : ($goods ? (int)$goods->stock : 0);
            $list[] = [
                'id' => (int)$row->id,
                'goodsId' => (int)$row->goods_id,
                'skuId' => (int)$row->sku_id,
                'num' => (int)$row->num,
                'invalid' => !$available,
                'title' => $goods->title ?? '商品已删除',
                'cover' => UploadService::url((string)($goods->cover ?? '')),
                'specText' => (string)($sku->spec_text ?? ''),
                'price' => $price,
                'stock' => $stock,
            ];
        }
        return $list;
    }

    private static function own(int $userId, int $cartId): Cart
    {
        /** @var Cart|null $row */
        $row = Cart::find()->where(['id' => $cartId, 'user_id' => $userId])->one();
        if (!$row) {
            throw new ApiException('购物车项不存在', ApiException::NOT_FOUND);
        }
        return $row;
    }
}
