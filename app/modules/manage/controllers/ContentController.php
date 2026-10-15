<?php

declare(strict_types=1);

namespace app\modules\manage\controllers;

use app\common\components\ApiException;
use app\common\model\Banner;
use app\common\model\Article;
use app\common\model\DiyPage;
use app\common\services\UploadService;

/**
 * 内容管理：轮播图 / 文章 / DIY 页面
 */
final class ContentController extends TenantAwareController
{
    protected string $permission = 'mall:content';

    /* ---------- 轮播图 ---------- */

    public function actionBanners(): \yii\web\Response
    {
        $query = Banner::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('sort ASC, id DESC');
        return $this->okList($query, static function (Banner $b) {
            $arr = $b->toArray();
            $arr['cover_url'] = UploadService::url((string)$b->cover);
            return $arr;
        });
    }

    public function actionSaveBanner(): \yii\web\Response
    {
        $id = $this->intInput('id');
        $cover = $this->strInput('cover');
        if ($cover === '') {
            $this->fail('请上传轮播图');
        }
        $banner = $id > 0
            ? Banner::find()->where(['id' => $id, 'tenant_id' => $this->tenantId()])->one()
            : new Banner();
        if ($id > 0 && !$banner) {
            throw new ApiException('轮播图不存在', ApiException::NOT_FOUND);
        }
        /** @var Banner $banner */
        $banner->tenant_id = $this->tenantId();
        $banner->title = $this->strInput('title');
        $banner->cover = $cover;
        $banner->link = $this->strInput('link');
        $banner->sort = (int)$this->input('sort', 100);
        if ($this->input('status') !== null) {
            $banner->status = (int)$this->input('status') === 1 ? 1 : 0;
        }
        $banner->save(false);
        return $this->ok(['banner' => $banner], '已保存');
    }

    public function actionDeleteBanner(): \yii\web\Response
    {
        Banner::deleteAll(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()]);
        return $this->ok(null, '已删除');
    }

    /* ---------- 文章 ---------- */

    public function actionArticles(): \yii\web\Response
    {
        $query = Article::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('sort ASC, id DESC');
        if (($kw = $this->strInput('keyword')) !== '') {
            $query->andWhere(['like', 'title', $kw]);
        }
        return $this->okList($query);
    }

    public function actionSaveArticle(): \yii\web\Response
    {
        $id = $this->intInput('id');
        $title = $this->strInput('title');
        if ($title === '') {
            $this->fail('请填写标题');
        }
        $article = $id > 0
            ? Article::find()->where(['id' => $id, 'tenant_id' => $this->tenantId()])->one()
            : new Article();
        if ($id > 0 && !$article) {
            throw new ApiException('文章不存在', ApiException::NOT_FOUND);
        }
        /** @var Article $article */
        $article->tenant_id = $this->tenantId();
        $article->title = $title;
        $article->cover = $this->strInput('cover');
        $article->summary = $this->strInput('summary');
        $article->content = (string)$this->input('content', '');
        $article->sort = (int)$this->input('sort', 100);
        if ($this->input('status') !== null) {
            $article->status = (int)$this->input('status') === 1 ? 1 : 0;
        }
        $article->save(false);
        return $this->ok(['article' => $article], '已保存');
    }

    public function actionDeleteArticle(): \yii\web\Response
    {
        Article::deleteAll(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()]);
        return $this->ok(null, '已删除');
    }

    /* ---------- DIY 页面 ---------- */

    public function actionDiyPages(): \yii\web\Response
    {
        $query = DiyPage::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('id DESC');
        return $this->okList($query, static function (DiyPage $p) {
            $arr = $p->toArray();
            $arr['config'] = \app\common\services\GoodsService::jsonArray($p->config);
            return $arr;
        });
    }

    public function actionSaveDiyPage(): \yii\web\Response
    {
        $id = $this->intInput('id');
        $title = $this->strInput('title');
        if ($title === '') {
            $this->fail('请填写页面名称');
        }
        $page = $id > 0
            ? DiyPage::find()->where(['id' => $id, 'tenant_id' => $this->tenantId()])->one()
            : new DiyPage();
        if ($id > 0 && !$page) {
            throw new ApiException('页面不存在', ApiException::NOT_FOUND);
        }
        /** @var DiyPage $page */
        $page->tenant_id = $this->tenantId();
        $page->title = $title;
        $page->config = json_encode((array)($this->input('config') ?? []), JSON_UNESCAPED_UNICODE);
        if ($this->input('status') !== null) {
            $page->status = (int)$this->input('status') === 1 ? 1 : 0;
        }
        // 设为首页时取消其它首页
        if ((int)$this->input('isHome', 0) === 1) {
            DiyPage::updateAll(['is_home' => 0], ['tenant_id' => $this->tenantId()]);
            $page->is_home = 1;
        } elseif ($this->input('isHome') !== null) {
            $page->is_home = 0;
        }
        $page->save(false);
        \Yii::$app->getCache()->delete("diy:home:{$this->tenantId()}");
        return $this->ok(['page' => $page], '已保存');
    }

    public function actionDeleteDiyPage(): \yii\web\Response
    {
        /** @var DiyPage|null $page */
        $page = DiyPage::find()
            ->where(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()])
            ->one();
        if ($page && (int)$page->is_home === 1) {
            $this->fail('首页不可删除，请先取消首页标记');
        }
        DiyPage::deleteAll(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()]);
        return $this->ok(null, '已删除');
    }
}
