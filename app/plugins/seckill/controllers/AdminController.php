<?php

declare(strict_types=1);

namespace app\plugins\seckill\controllers;

use app\common\components\ApiException;
use app\plugins\PluginAdminController;
use app\plugins\seckill\models\SeckillGoods;
use app\plugins\seckill\models\SeckillSession;

/**
 * 秒杀管理端：配置 / 场次管理
 */
final class AdminController extends PluginAdminController
{
    protected function pluginCode(): string
    {
        return 'seckill';
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
        return $this->ok(null, '秒杀配置已保存');
    }

    /** 创建秒杀场次 */
    public function actionSaveSession(): \yii\web\Response
    {
        $session = new SeckillSession();
        $session->tenant_id = $this->tenantId();
        $session->name = $this->strInput('name', '秒杀场次');
        $session->start_at = (int)strtotime((string)$this->input('startAt', 'now'));
        $session->end_at = (int)strtotime((string)$this->input('endAt', '+2 hours'));
        $session->status = 1;
        if ($session->end_at <= $session->start_at) {
            throw new ApiException('结束时间必须晚于开始时间', ApiException::VALIDATE);
        }
        $session->save(false);
        $items = (array)($this->input('goods') ?? []);
        foreach ($items as $item) {
            $item = (array)$item;
            $goodsId = (int)($item['goodsId'] ?? 0);
            if ($goodsId <= 0 || !\app\common\model\Goods::find()->where(['id' => $goodsId, 'tenant_id' => $this->tenantId()])->exists()) {
                continue;
            }
            $row = new SeckillGoods();
            $row->tenant_id = $this->tenantId();
            $row->session_id = (int)$session->id;
            $row->goods_id = $goodsId;
            $row->seckill_price = (string)max(0.01, (float)($item['seckillPrice'] ?? 0));
            $row->seckill_stock = (int)($item['stock'] ?? 0);
            $row->per_limit = (int)($item['perLimit'] ?? 1);
            $row->save(false);
        }
        return $this->ok(['sessionId' => (int)$session->id], '场次已保存');
    }

    /** 场次列表 */
    public function actionSessions(): \yii\web\Response
    {
        $query = SeckillSession::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('id DESC');
        return $this->okList($query);
    }
}