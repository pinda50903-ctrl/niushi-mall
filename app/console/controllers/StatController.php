<?php

declare(strict_types=1);

namespace app\console\controllers;

use app\common\services\OrderService;
use app\common\services\StatService;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * 定时任务：php yii stat/xxx
 */
final class StatController extends Controller
{
    /** 汇总昨日数据（建议每天 00:05 执行） */
    public function actionDaily(string $date = ''): int
    {
        StatService::summarizeDay($date);
        $this->stdout('已完成 ' . ($date ?: date('Y-m-d', time() - 86400)) . " 数据汇总\n");
        return ExitCode::OK;
    }

    /** 关闭超时未支付订单（建议每 5 分钟执行） */
    public function actionCloseOrders(): int
    {
        $closed = OrderService::closeUnpaid();
        $this->stdout("已关闭 {$closed} 个超时订单\n");
        return ExitCode::OK;
    }

    /** 自动确认收货（发货 7 天后，建议每小时执行） */
    public function actionAutoReceive(): int
    {
        $days = (int)\app\common\services\SettingService::get('trade', 1)['auto_receive_days'] ?? 7;
        $deadline = time() - $days * 86400;
        $count = \app\common\model\Order::updateAll(
            ['status' => \app\common\model\Order::STATUS_FINISHED, 'finished_at' => time()],
            [
                'status' => \app\common\model\Order::STATUS_DELIVERED,
                'and',
                ['<', 'delivered_at', $deadline],
            ]
        );
        $this->stdout("自动收货 {$count} 单\n");
        return ExitCode::OK;
    }
}
