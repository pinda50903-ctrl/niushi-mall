<?php

declare(strict_types=1);

namespace app\plugins\check_in\controllers;

use app\plugins\PluginRestController;
use app\plugins\check_in\services\CheckInService;

/**
 * 签到用户端：签到 / 状态日历 / 补签
 */
final class CheckController extends PluginRestController
{
    protected function pluginCode(): string
    {
        return 'check_in';
    }

    /** 今日签到 */
    public function actionDo(): \yii\web\Response
    {
        $result = CheckInService::sign($this->tenantId(), $this->currentUserId());
        return $this->ok($result, '签到成功，积分 +');
    }

    /** 签到状态与月度日历（month=202610） */
    public function actionStatus(): \yii\web\Response
    {
        $userId = $this->currentUserId();
        return $this->ok(CheckInService::status($this->tenantId(), $userId, $this->intInput('month', 0)));
    }

    /** 补签 */
    public function actionMakeup(): \yii\web\Response
    {
        $result = CheckInService::makeup($this->tenantId(), $this->currentUserId(), $this->intInput('ymd'));
        return $this->ok($result, '补签成功');
    }
}
