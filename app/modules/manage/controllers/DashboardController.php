<?php

declare(strict_types=1);

namespace app\modules\manage\controllers;

use app\common\services\StatService;

/**
 * 仪表盘
 */
final class DashboardController extends TenantAwareController
{
    protected string $permission = 'mall:dashboard';

    public function actionIndex(): \yii\web\Response
    {
        return $this->ok(StatService::dashboard($this->tenantId()));
    }

    public function actionTrend(): \yii\web\Response
    {
        return $this->ok(['list' => StatService::trend($this->tenantId(), max(7, min(60, $this->intInput('days', 14))))]);
    }
}
