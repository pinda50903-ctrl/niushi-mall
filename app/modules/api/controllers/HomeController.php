<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\common\model\Banner;
use app\common\model\Category;
use app\common\model\DiyPage;
use app\common\services\GoodsService;
use app\common\services\StatService;
use app\common\services\TenantContext;
use app\common\services\UploadService;

/**
 * 首页与内容
 */
final class HomeController extends UserAwareController
{
    /** 首页：DIY 页面 + 兜底数据（banner/分类/推荐商品） */
    public function actionIndex(): \yii\web\Response
    {
        $tenantId = $this->tenantId();
        /** @var DiyPage|null $home */
        $home = DiyPage::find()
            ->where(['tenant_id' => $tenantId, 'is_home' => 1, 'status' => 1])
            ->one();

        $banners = Banner::find()
            ->where(['tenant_id' => $tenantId, 'status' => 1])
            ->orderBy('sort ASC, id DESC')
            ->limit(10)
            ->all();

        $categories = Category::find()
            ->where(['tenant_id' => $tenantId, 'parent_id' => 0, 'is_visible' => 1])
            ->orderBy('sort ASC, id ASC')
            ->limit(8)
            ->all();

        $recommend = GoodsService::search($tenantId, ['isRecommend' => 1], 1, 6);

        $user = $this->currentUser();
        StatService::trackVisit(
            $tenantId,
            $user ? (int)$user->id : 0,
            (string)$this->input('channel', 'h5'),
            (string)$this->input('path', '/pages/index/index')
        );

        return $this->ok([
            'tenant' => [
                'id' => (int)TenantContext::resolve()->id,
                'name' => TenantContext::resolve()->name,
                'logo' => UploadService::url((string)TenantContext::resolve()->logo),
                'phone' => TenantContext::resolve()->phone,
            ],
            'diy' => $home ? GoodsService::jsonArray($home->config) : null,
            'banners' => array_map(static function (Banner $b) {
                $arr = $b->toArray();
                $arr['cover'] = UploadService::url((string)$b->cover);
                return $arr;
            }, $banners),
            'categories' => array_map(static fn (Category $c) => [
                'id' => (int)$c->id,
                'name' => $c->name,
                'icon' => UploadService::url((string)$c->icon),
            ], $categories),
            'recommend' => array_map(
                static fn ($g) => GoodsService::brief($g),
                $recommend->getModels()
            ),
        ]);
    }

    /** DIY 页面详情 */
    public function actionPage(): \yii\web\Response
    {
        $id = $this->intInput('id');
        /** @var DiyPage|null $page */
        $page = DiyPage::find()
            ->where(['id' => $id, 'tenant_id' => $this->tenantId(), 'status' => 1])
            ->one();
        if (!$page) {
            $this->fail('页面不存在', \app\common\components\ApiException::NOT_FOUND);
        }
        return $this->ok([
            'title' => $page->title,
            'config' => GoodsService::jsonArray($page->config),
        ]);
    }

    /** 文章列表 */
    public function actionArticles(): \yii\web\Response
    {
        $query = \app\common\model\Article::find()
            ->where(['tenant_id' => $this->tenantId(), 'status' => 1])
            ->orderBy('sort ASC, id DESC');
        return $this->okList($query, static function (\app\common\model\Article $a) {
            $arr = $a->toArray();
            unset($arr['content']);
            $arr['cover'] = UploadService::url((string)$a->cover);
            return $arr;
        });
    }

    public function actionArticle(): \yii\web\Response
    {
        /** @var \app\common\model\Article|null $article */
        $article = \app\common\model\Article::find()
            ->where(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId(), 'status' => 1])
            ->one();
        if (!$article) {
            $this->fail('文章不存在', \app\common\components\ApiException::NOT_FOUND);
        }
        return $this->ok(['article' => $article]);
    }
}
