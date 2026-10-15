<?php

declare(strict_types=1);

namespace app\modules\manage\controllers;

use app\common\components\ApiException;
use app\common\model\FreightTemplate;

/**
 * 运费模板
 */
final class FreightController extends TenantAwareController
{
    protected string $permission = 'mall:freight';

    public function actionIndex(): \yii\web\Response
    {
        $query = \app\common\model\FreightTemplate::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('id ASC');
        return $this->okList($query);
    }

    public function actionSave(): \yii\web\Response
    {
        $id = $this->intInput('id');
        $name = $this->strInput('name');
        if ($name === '') {
            $this->fail('请填写模板名称');
        }
        $tpl = $id > 0
            ? FreightTemplate::find()->where(['id' => $id, 'tenant_id' => $this->tenantId()])->one()
            : new FreightTemplate();
        if ($id > 0 && !$tpl) {
            throw new ApiException('模板不存在', ApiException::NOT_FOUND);
        }
        /** @var FreightTemplate $tpl */
        $tpl->tenant_id = $this->tenantId();
        $tpl->name = $name;
        $tpl->charge_type = (int)$this->input('chargeType', 0) === 1 ? 1 : 0;
        $tpl->base_amount = (string)max(0, (float)$this->input('baseAmount', 0));
        $tpl->base_quantity = (string)max(0, (float)$this->input('baseQuantity', 1));
        $tpl->step_amount = (string)max(0, (float)$this->input('stepAmount', 0));
        $tpl->step_quantity = (string)max(0, (float)$this->input('stepQuantity', 1));
        $tpl->free_order_amount = (string)max(0, (float)$this->input('freeOrderAmount', 0));
        $tpl->excluded_regions = json_encode((array)($this->input('excludedRegions') ?? []), JSON_UNESCAPED_UNICODE);
        $tpl->save(false);
        return $this->ok(['template' => $tpl], '已保存');
    }

    public function actionDelete(): \yii\web\Response
    {
        /** @var FreightTemplate|null $tpl */
        $tpl = FreightTemplate::find()
            ->where(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()])
            ->one();
        if (!$tpl) {
            throw new ApiException('模板不存在', ApiException::NOT_FOUND);
        }
        $default = (int)(\app\common\services\SettingService::get('trade', $this->tenantId())['freight_template_id'] ?? 0);
        if ($default === (int)$tpl->id) {
            $this->fail('该模板为默认模板，请先更换默认设置');
        }
        $tpl->delete();
        return $this->ok(null, '已删除');
    }
}
