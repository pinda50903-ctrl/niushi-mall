<?php

declare(strict_types=1);

namespace app\modules\manage\controllers;

use app\common\components\ApiException;
use app\common\model\Category;
use app\common\model\Goods;
use app\common\model\GoodsSku;
use app\common\services\GoodsService;
use app\common\services\UploadService;
use Yii;

/**
 * 商品管理（含规格 SKU）
 */
final class GoodsController extends TenantAwareController
{
    protected string $permission = 'mall:goods';

    public function actionIndex(): \yii\web\Response
    {
        $query = Goods::find()->where(['tenant_id' => $this->tenantId()]);
        if (($kw = $this->strInput('keyword')) !== '') {
            $query->andWhere(['like', 'title', $kw]);
        }
        if ($this->input('categoryId')) {
            $query->andWhere(['category_id' => $this->intInput('categoryId')]);
        }
        if ($this->input('status') !== null && $this->input('status') !== '') {
            $query->andWhere(['status' => (int)$this->input('status')]);
        }
        $query->orderBy('sort ASC, id DESC');
        return $this->okList($query, static function (Goods $goods) {
            $arr = $goods->toArray();
            $arr['slides'] = GoodsService::jsonArray($goods->slides);
            $arr['cover_url'] = UploadService::url((string)$goods->cover);
            return $arr;
        });
    }

    public function actionView(): \yii\web\Response
    {
        $goods = $this->find();
        $data = $goods->toArray();
        $data['slides'] = GoodsService::jsonArray($goods->slides);
        $data['skus'] = GoodsSku::find()->where(['goods_id' => $goods->id])->orderBy('price')->all();
        return $this->ok(['goods' => $data]);
    }

    /**
     * 保存商品（新增/编辑），skus 一并提交
     * body: {id?, title, categoryId, price, skus?: [{specText, price, stock,...}]}
     */
    public function actionSave(): \yii\web\Response
    {
        $id = $this->intInput('id');
        $title = $this->strInput('title');
        if ($title === '') {
            $this->fail('请填写商品标题');
        }
        $goods = $id > 0 ? $this->find() : new Goods();
        $isNew = $goods->isNewRecord;

        $goods->tenant_id = $this->tenantId();
        $goods->title = $title;
        $goods->subtitle = $this->strInput('subtitle');
        $goods->category_id = $this->intInput('categoryId');
        $goods->cover = $this->strInput('cover');
        $goods->slides = json_encode((array)($this->input('slides') ?? []), JSON_UNESCAPED_UNICODE);
        $goods->detail = (string)$this->input('detail', '');
        $goods->unit = $this->strInput('unit', '件');
        $goods->price = (string)max(0, (float)$this->input('price', 0));
        $goods->line_price = (string)max(0, (float)$this->input('linePrice', 0));
        $goods->cost_price = (string)max(0, (float)$this->input('costPrice', 0));
        $goods->stock = (int)$this->input('stock', 0);
        $goods->weight = (string)max(0, (float)$this->input('weight', 0));
        $goods->virtual_sales = (int)$this->input('virtualSales', 0);
        $goods->is_sku_enabled = (int)$this->input('isSkuEnabled', 0) === 1 ? 1 : 0;
        $goods->is_recommend = (int)$this->input('isRecommend', 0) === 1 ? 1 : 0;
        $goods->is_free_shipping = (int)$this->input('isFreeShipping', 0) === 1 ? 1 : 0;
        if ($this->input('status') !== null) {
            $goods->status = (int)$this->input('status') === 1 ? 1 : 0;
        }
        $goods->sort = (int)$this->input('sort', 100);
        $goods->save(false);

        // SKU 同步
        if ((int)$goods->is_sku_enabled === 1) {
            $incoming = (array)($this->input('skus') ?? []);
            $keepIds = [];
            $minPrice = null;
            foreach ($incoming as $row) {
                $row = (array)$row;
                $skuId = (int)($row['id'] ?? 0);
                /** @var GoodsSku|null $sku */
                $sku = $skuId > 0
                    ? GoodsSku::find()->where(['id' => $skuId, 'goods_id' => $goods->id])->one()
                    : null;
                if (!$sku) {
                    $sku = new GoodsSku();
                    $sku->goods_id = (int)$goods->id;
                }
                $specs = (array)($row['specValues'] ?? []);
                $sku->spec_values = json_encode($specs, JSON_UNESCAPED_UNICODE);
                $sku->spec_text = (string)($row['specText'] ?? implode('/', array_map(
                    static fn ($s) => (string)($s['value'] ?? ''),
                    $specs
                )));
                $sku->price = (string)max(0, (float)($row['price'] ?? 0));
                $sku->line_price = (string)max(0, (float)($row['linePrice'] ?? 0));
                $sku->stock = (int)($row['stock'] ?? 0);
                $sku->weight = (string)max(0, (float)($row['weight'] ?? 0));
                $sku->code = (string)($row['code'] ?? '');
                $sku->save(false);
                $keepIds[] = (int)$sku->id;
                $minPrice = $minPrice === null ? (float)$sku->price : min($minPrice, (float)$sku->price);
            }
            GoodsSku::deleteAll(['and', ['goods_id' => $goods->id], ['not in', 'id', $keepIds ?: [0]]]);
            if ($minPrice !== null) {
                $goods->price = (string)$minPrice;
                $goods->save(false);
            }
        } else {
            GoodsSku::deleteAll(['goods_id' => $goods->id]);
        }

        if ($isNew) {
            $this->logOperation(['op' => 'create', 'goods_id' => $goods->id]);
        }
        return $this->ok(['goods' => $goods], '商品已保存');
    }

    /** 上下架 */
    public function actionToggle(): \yii\web\Response
    {
        $goods = $this->find();
        $goods->status = (int)$goods->status === 1 ? 0 : 1;
        $goods->save(false);
        return $this->ok(['status' => (int)$goods->status], '已更新');
    }

    public function actionDelete(): \yii\web\Response
    {
        $goods = $this->find();
        $sold = (int)Yii::$app->db->createCommand(
            'SELECT COUNT(*) FROM {{%order_item}} oi INNER JOIN {{%order}} o ON o.id = oi.order_id '
            . 'WHERE oi.goods_id = :g AND o.status NOT IN (50)',
            [':g' => $goods->id]
        )->queryScalar();
        if ($sold > 0) {
            $this->fail('商品存在进行中的订单，不可删除（可下架）');
        }
        GoodsSku::deleteAll(['goods_id' => $goods->id]);
        $goods->delete();
        $this->logOperation(['op' => 'delete', 'goods_id' => $goods->id]);
        return $this->ok(null, '已删除');
    }

    private function find(): Goods
    {
        /** @var Goods|null $goods */
        $goods = Goods::find()
            ->where(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()])
            ->one();
        if (!$goods) {
            throw new ApiException('商品不存在', ApiException::NOT_FOUND);
        }
        return $goods;
    }
}
