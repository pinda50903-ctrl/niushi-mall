<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\common\model\Category;
use app\common\services\UploadService;

/**
 * 分类
 */
final class CategoryController extends UserAwareController
{
    public function actionIndex(): \yii\web\Response
    {
        $rows = Category::find()
            ->where(['tenant_id' => $this->tenantId(), 'is_visible' => 1])
            ->orderBy('parent_id ASC, sort ASC, id ASC')
            ->all();
        $nodes = array_map(static function (Category $c) {
            return [
                'id' => (int)$c->id,
                'parentId' => (int)$c->parent_id,
                'name' => $c->name,
                'icon' => UploadService::url((string)$c->icon),
            ];
        }, $rows);
        $top = array_values(array_filter($nodes, static fn ($n) => $n['parentId'] === 0));
        foreach ($top as &$parent) {
            $parent['children'] = array_values(array_filter(
                $nodes,
                static fn ($n) => $n['parentId'] === $parent['id']
            ));
        }
        return $this->ok(['list' => $top]);
    }
}
