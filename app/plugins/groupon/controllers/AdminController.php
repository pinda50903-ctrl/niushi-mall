<?php

declare(strict_types=1);

namespace app\plugins\groupon\controllers;

use app\plugins\PluginAdminController;

/**
 * 拼团管理端接口：插件配置 + 活动管理
 */
final class AdminController extends PluginAdminController
{
    protected function pluginCode(): string
    {
        return 'groupon';
    }

    /** 读取插件配置 */
    public function actionConfig(): \yii\web\Response
    {
        return $this->ok([
            'config' => \app\common\services\PluginSettingService::get($this->pluginCode(), $this->tenantId()),
        ]);
    }

    /** 保存插件配置 */
    public function actionSaveConfig(): \yii\web\Response
    {
        $value = (array)($this->input('config') ?? []);
        \app\common\services\PluginSettingService::save($this->pluginCode(), $value, $this->tenantId());
        return $this->ok(null, '拼团配置已保存');
    }

    /** 创建拼团活动 */
    public function actionSaveActivity(): \yii\web\Response
    {
        $goodsId = $this->intInput('goodsId');
        if ($goodsId <= 0 || !\app\common\model\Goods::find()->where(['id' => $goodsId, 'tenant_id' => $this->tenantId()])->exists()) {
            $this->fail('商品不存在');
        }
        $groupPrice = max(0.01, (float)$this->input('groupPrice', 0));
        $activity = new \app\plugins\groupon\models\GrouponActivity();
        $activity->tenant_id = $this->tenantId();
        $activity->goods_id = $goodsId;
        $activity->title = $this->strInput('title', '拼团活动');
        $activity->group_price = (string)$groupPrice;
        $activity->group_size = max(2, (int)$this->input('groupSize', 3));
        $activity->time_limit_hours = max(1, (int)$this->input('timeLimitHours', 24));
        $activity->stock = max(1, (int)$this->input('stock', 100));
        $activity->virtual_leader = 0;
        $activity->start_at = (int)strtotime((string)$this->input('startAt', 'now'));
        $activity->end_at = (int)strtotime((string)$this->input('endAt', '+7 days'));
        $activity->status = 1;
        $activity->created_at = time();
        $activity->updated_at = time();
        $activity->save(false);
        return $this->ok(['activityId' => (int)$activity->id], '拼团活动已创建');
    }

    /** 活动列表 */
    public function actionActivities(): \yii\web\Response
    {
        $query = \app\plugins\groupon\models\GrouponActivity::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('id DESC');
        return $this->okList($query);
    }
}