<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\common\model\BrowseLog;
use app\common\model\Goods;
use app\common\model\UserFavorite;
use app\common\services\GoodsService;

/**
 * 商品
 */
final class GoodsController extends UserAwareController
{
    public function actionList(): \yii\web\Response
    {
        [$page, $size] = $this->pageRange();
        $provider = GoodsService::search($this->tenantId(), [
            'categoryId' => $this->intInput('categoryId'),
            'keyword' => $this->strInput('keyword'),
            'isRecommend' => $this->input('isRecommend'),
            'sortType' => $this->intInput('sortType'),
        ], $page, $size);
        return $this->ok([
            'list' => array_map(static fn ($g) => GoodsService::brief($g), $provider->getModels()),
            'total' => $provider->getTotalCount(),
            'page' => $page,
            'pageSize' => $size,
        ]);
    }

    public function actionDetail(): \yii\web\Response
    {
        $tenantId = $this->tenantId();
        $goodsId = $this->intInput('id');
        $data = GoodsService::detail($tenantId, $goodsId);
        $data['cover'] = \app\common\services\UploadService::url((string)$data['cover']);
        $data['slides'] = array_map(
            static fn ($s) => \app\common\services\UploadService::url((string)$s),
            $data['slides']
        );
        foreach ($data['skus'] as &$sku) {
            unset($sku['created_at'], $sku['updated_at']);
        }

        // 浏览计数与足迹
        Goods::updateAllCounters(['view_count' => 1], ['id' => $goodsId]);
        $user = $this->currentUser();
        $userId = $user && (int)$user->id > 0 ? (int)$user->id : 0;
        if ($userId > 0) {
            $log = new BrowseLog();
            $log->tenant_id = $tenantId;
            $log->user_id = $userId;
            $log->goods_id = $goodsId;
            $log->created_at = time();
            $log->save(false);
            $fav = UserFavorite::find()
                ->where(['user_id' => $userId, 'goods_id' => $goodsId])
                ->exists();
            $data['is_favorite'] = $fav;
        }
        return $this->ok(['goods' => $data]);
    }

    /* ---------- 收藏 ---------- */

    public function actionFavoriteToggle(): \yii\web\Response
    {
        $userId = $this->userId();
        $goodsId = $this->intInput('goodsId');
        $existing = UserFavorite::find()
            ->where(['user_id' => $userId, 'goods_id' => $goodsId])
            ->one();
        if ($existing) {
            $existing->delete();
            return $this->ok(['favorite' => false], '已取消收藏');
        }
        $fav = new UserFavorite();
        $fav->tenant_id = $this->tenantId();
        $fav->user_id = $userId;
        $fav->goods_id = $goodsId;
        $fav->save(false);
        return $this->ok(['favorite' => true], '已收藏');
    }

    public function actionFavorites(): \yii\web\Response
    {
        $userId = $this->userId();
        $query = UserFavorite::find()
            ->where(['user_id' => $userId])
            ->orderBy('id DESC');
        return $this->okList($query, static function (UserFavorite $fav) {
            $goods = Goods::find()->where(['id' => $fav->goods_id])->one();
            return [
                'id' => (int)$fav->id,
                'goods' => $goods ? GoodsService::brief($goods) : null,
            ];
        });
    }

    public function actionBrowse(): \yii\web\Response
    {
        $userId = $this->userId();
        $rows = BrowseLog::find()
            ->where(['user_id' => $userId])
            ->orderBy('id DESC')
            ->limit(50)
            ->all();
        $seen = [];
        $list = [];
        foreach ($rows as $row) {
            $gid = (int)$row->goods_id;
            if (isset($seen[$gid])) {
                continue;
            }
            $seen[$gid] = true;
            $goods = Goods::find()->where(['id' => $gid])->one();
            $list[] = [
                'time' => (int)$row->created_at,
                'goods' => $goods ? GoodsService::brief($goods) : null,
            ];
            if (count($list) >= 20) {
                break;
            }
        }
        return $this->ok(['list' => $list]);
    }
}
