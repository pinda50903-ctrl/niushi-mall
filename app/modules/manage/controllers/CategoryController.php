<?php

declare(strict_types=1);

namespace app\modules\manage\controllers;

use app\common\components\ApiException;
use app\common\model\Category;
use app\common\model\Goods;
use app\common\services\UploadService;
use Yii;

/**
 * 商品分类（两级树）
 */
final class CategoryController extends TenantAwareController
{
    protected string $permission = 'mall:category';

    public function actionIndex(): \yii\web\Response
    {
        $rows = Category::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('parent_id ASC, sort ASC, id ASC')
            ->all();
        $nodes = array_map(static function (Category $c) {
            $arr = $c->toArray();
            $arr['icon_url'] = UploadService::url((string)$c->icon);
            return $arr;
        }, $rows);
        $top = array_values(array_filter($nodes, static fn ($n) => (int)$n['parent_id'] === 0));
        foreach ($top as &$parent) {
            $parent['children'] = array_values(array_filter(
                $nodes,
                static fn ($n) => (int)$n['parent_id'] === (int)$parent['id']
            ));
        }
        return $this->ok(['list' => $top]);
    }

    public function actionSave(): \yii\web\Response
    {
        $id = $this->intInput('id');
        $name = $this->strInput('name');
        if ($name === '') {
            $this->fail('请填写分类名称');
        }
        $category = $id > 0
            ? Category::find()->where(['id' => $id, 'tenant_id' => $this->tenantId()])->one()
            : new Category();
        if ($id > 0 && !$category) {
            throw new ApiException('分类不存在', ApiException::NOT_FOUND);
        }
        $parentId = $this->intInput('parentId');
        if ($parentId > 0) {
            $parent = Category::find()->where(['id' => $parentId, 'tenant_id' => $this->tenantId()])->one();
            if (!$parent) {
                $this->fail('父分类不存在');
            }
            if ((int)$parent->parent_id > 0) {
                $this->fail('最多两级分类');
            }
        }
        /** @var Category $category */
        $category->tenant_id = $this->tenantId();
        $category->parent_id = $parentId;
        $category->name = $name;
        $category->icon = $this->strInput('icon');
        $category->sort = (int)$this->input('sort', 100);
        if ($this->input('isVisible') !== null) {
            $category->is_visible = (int)$this->input('isVisible') === 1 ? 1 : 0;
        }
        $category->save(false);
        return $this->ok(['category' => $category], '已保存');
    }

    public function actionDelete(): \yii\web\Response
    {
        /** @var Category|null $category */
        $category = Category::find()
            ->where(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()])
            ->one();
        if (!$category) {
            throw new ApiException('分类不存在', ApiException::NOT_FOUND);
        }
        if (Category::find()->where(['parent_id' => $category->id])->exists()) {
            $this->fail('请先删除子分类');
        }
        if (Goods::find()->where(['category_id' => $category->id])->exists()) {
            $this->fail('该分类下存在商品，不可删除');
        }
        $category->delete();
        return $this->ok(null, '已删除');
    }
}
