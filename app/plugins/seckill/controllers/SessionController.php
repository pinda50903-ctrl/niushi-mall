<?php

declare(strict_types=1);

namespace app\plugins\seckill\controllers;

use app\plugins\PluginRestController;
use app\plugins\seckill\models\SeckillGoods;
use app\plugins\seckill\models\SeckillSession;

/**
 * 秒杀用户端
 */
final class SessionController extends PluginRestController
{
    protected function pluginCode(): string
    {
        return 'seckill';
    }

    /** 当前/即将开始的场次（带商品） */
    public function actionIndex(): \yii\web\Response
    {
        $tenantId = $this->tenantId();
        $sessions = SeckillSession::find()
            ->where(['tenant_id' => $tenantId, 'status' => 1])
            ->andWhere(['>=', 'end_at', time()])
            ->orderBy('start_at ASC')
            ->limit(5)
            ->all();
        $now = time();
        $list = [];
        foreach ($sessions as $session) {
            /** @var SeckillSession $session */
            $goods = SeckillGoods::find()
                ->where(['session_id' => $session->id])
                ->andWhere(['>', 'seckill_stock', 0])
                ->all();
            $goodsIds = array_map(static fn ($g) => (int)$g->goods_id, $goods);
            $goodsMap = $goodsIds === [] ? [] : \app\common\model\Goods::find()
                ->select('id, title, cover, price')
                ->where(['id' => $goodsIds])
                ->asArray()
                ->indexBy('id')
                ->all();
            $list[] = [
                'id' => (int)$session->id,
                'name' => $session->name,
                'startAt' => (int)$session->start_at,
                'endAt' => (int)$session->end_at,
                'isLive' => $now >= (int)$session->start_at,
                'goods' => array_map(static function (SeckillGoods $g) use ($goodsMap) {
                    $base = $goodsMap[(int)$g->goods_id] ?? [];
                    return [
                        'id' => (int)$g->id,
                        'goodsId' => (int)$g->goods_id,
                        'title' => $base['title'] ?? '',
                        'cover' => isset($base['cover']) ? \app\common\services\UploadService::url((string)$base['cover']) : '',
                        'originPrice' => isset($base['price']) ? (float)$base['price'] : 0,
                        'seckillPrice' => (float)$g->seckill_price,
                        'stock' => (int)$g->seckill_stock,
                        'soldCount' => (int)$g->sold_count,
                        'perLimit' => (int)$g->per_limit,
                    ];
                }, $goods),
            ];
        }
        return $this->ok(['list' => $list]);
    }
}
