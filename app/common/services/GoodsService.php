<?php

declare(strict_types=1);

namespace app\common\services;

use app\common\components\ApiException;
use app\common\model\Goods;
use app\common\model\GoodsSku;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * 商品查询与装配
 */
final class GoodsService
{
    public static function search(int $tenantId, array $filters, int $page, int $pageSize): ActiveDataProvider
    {
        $query = Goods::find()
            ->where(['tenant_id' => $tenantId, 'status' => Goods::STATUS_ON]);

        $categoryId = (int)($filters['categoryId'] ?? 0);
        if ($categoryId > 0) {
            // 含子分类
            $ids = self::descendantCategoryIds($tenantId, $categoryId);
            $query->andWhere(['category_id' => $ids ?: [$categoryId]]);
        }
        if (($keyword = trim((string)($filters['keyword'] ?? ''))) !== '') {
            $query->andWhere(['like', 'title', $keyword]);
        }
        if (isset($filters['isRecommend'])) {
            $query->andWhere(['is_recommend' => (int)$filters['isRecommend']]);
        }
        $sort = match ((int)($filters['sortType'] ?? 0)) {
            1 => ['sold_count' => SORT_DESC],
            2 => ['price' => SORT_ASC],
            3 => ['price' => SORT_DESC],
            default => ['sort' => SORT_ASC, 'id' => SORT_DESC],
        };
        $query->orderBy($sort);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
        ]);
    }

    public static function detail(int $tenantId, int $goodsId): array
    {
        /** @var Goods|null $goods */
        $goods = Goods::find()
            ->where(['id' => $goodsId, 'tenant_id' => $tenantId])
            ->one();
        if (!$goods) {
            throw new ApiException('商品不存在或已下架', ApiException::NOT_FOUND);
        }
        $skus = GoodsSku::find()
            ->where(['goods_id' => $goodsId])
            ->orderBy('price')
            ->all();

        $data = $goods->toArray();
        $data['slides'] = self::jsonArray($goods->slides);
        $data['detail'] = $goods->detail;
        $data['is_favorite'] = false;
        $data['skus'] = array_map(static fn (GoodsSku $s) => $s->toArray(), $skus);
        return $data;
    }

    /** 校验购买：返回 [goods, sku, 单价, 库存] */
    public static function resolveBuyable(int $tenantId, int $goodsId, int $skuId): array
    {
        /** @var Goods|null $goods */
        $goods = Goods::find()
            ->where(['id' => $goodsId, 'tenant_id' => $tenantId, 'status' => 1])
            ->one();
        if (!$goods) {
            throw new ApiException('商品不存在或已下架', ApiException::NOT_FOUND);
        }
        $sku = null;
        if ((int)$goods->is_sku_enabled === 1) {
            $sku = GoodsSku::find()->where(['id' => $skuId, 'goods_id' => $goodsId])->one();
            if (!$sku) {
                throw new ApiException('请选择商品规格', ApiException::VALIDATE);
            }
            $price = (float)$sku->price;
            $stock = (int)$sku->stock;
        } else {
            $price = (float)$goods->price;
            $stock = (int)$goods->stock;
        }
        return [$goods, $sku, $price, $stock];
    }

    public static function decrementStock(int $goodsId, int $skuId, int $num): void
    {
        $hit = Goods::updateAllCounters(['stock' => -$num], ['id' => $goodsId]);
        if (!$hit) {
            throw new ApiException('商品扣减库存失败', ApiException::VALIDATE);
        }
        if ($skuId > 0) {
            GoodsSku::updateAllCounters(['stock' => -$num], ['id' => $skuId]);
        }
    }

    public static function incrementStock(int $goodsId, int $skuId, int $num): void
    {
        Goods::updateAllCounters(['stock' => $num], ['id' => $goodsId]);
        if ($skuId > 0) {
            GoodsSku::updateAllCounters(['stock' => $num], ['id' => $skuId]);
        }
    }

    public static function jsonArray(mixed $raw): array
    {
        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;
        return is_array($decoded) ? $decoded : [];
    }

    /** 商品列表行精简格式（列表页） */
    public static function brief(Goods $goods): array
    {
        return [
            'id' => (int)$goods->id,
            'title' => $goods->title,
            'subtitle' => $goods->subtitle,
            'cover' => UploadService::url((string)$goods->cover),
            'price' => (float)$goods->price,
            'linePrice' => (float)$goods->line_price,
            'soldCount' => (int)$goods->sold_count + (int)$goods->virtual_sales,
            'stock' => (int)$goods->stock,
        ];
    }

    private static function descendantCategoryIds(int $tenantId, int $parentId): array
    {
        $rows = \app\common\model\Category::find()
            ->select('id, parent_id')
            ->where(['tenant_id' => $tenantId])
            ->asArray()
            ->all();
        $childrenMap = [];
        foreach ($rows as $row) {
            $childrenMap[(int)$row['parent_id']][] = (int)$row['id'];
        }
        $ids = [];
        $stack = [$parentId];
        while ($stack) {
            $pid = array_pop($stack);
            foreach ($childrenMap[$pid] ?? [] as $cid) {
                $ids[] = $cid;
                $stack[] = $cid;
            }
        }
        return $ids;
    }
}
