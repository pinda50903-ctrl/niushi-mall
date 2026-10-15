<?php

declare(strict_types=1);

namespace app\plugins\check_in\controllers;

use app\plugins\PluginAdminController;
use app\plugins\check_in\models\CheckInRecord;
use app\plugins\check_in\services\CheckInService;

/**
 * 签到管理端：配置 / 打卡记录
 */
final class AdminController extends PluginAdminController
{
    protected function pluginCode(): string
    {
        return 'check_in';
    }

    /** 读取插件配置 */
    public function actionConfig(): \yii\web\Response
    {
        return $this->ok([
            'config' => CheckInService::config($this->tenantId()),
        ]);
    }

    /** 保存插件配置 */
    public function actionSaveConfig(): \yii\web\Response
    {
        $input = (array)($this->input('config') ?? []);
        $value = [
            'base_integral' => max(0, min(1000, (int)($input['base_integral'] ?? 5))),
            'seven_bonus' => max(0, min(10000, (int)($input['seven_bonus'] ?? 10))),
            'thirty_bonus' => max(0, min(100000, (int)($input['thirty_bonus'] ?? 50))),
            'makeup_cost' => max(0, min(1000, (int)($input['makeup_cost'] ?? 20))),
        ];
        \app\common\services\PluginSettingService::save($this->pluginCode(), $value, $this->tenantId());
        return $this->ok(null, '签到配置已保存');
    }

    /** 打卡记录列表 */
    public function actionRecords(): \yii\web\Response
    {
        $query = CheckInRecord::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('id DESC');
        return $this->okList($query, static function (CheckInRecord $row): array {
            $user = \app\common\model\User::find()
                ->select('nickname')
                ->where(['id' => (int)$row->user_id])->asArray()->one();
            return [
                'id' => (int)$row->id,
                'userId' => (int)$row->user_id,
                'nickname' => $user['nickname'] ?? ('用户#' . $row->user_id),
                'ymd' => (int)$row->ymd,
                'continuous' => (int)$row->continuous,
                'rewardIntegral' => (int)$row->reward_integral,
                'isMakeup' => (int)$row->is_makeup === 1,
                'createdAt' => (int)$row->created_at,
            ];
        });
    }
}
