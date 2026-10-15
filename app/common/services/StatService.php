<?php

declare(strict_types=1);

namespace app\common\services;

use app\common\model\DailyStat;
use app\common\model\Order;
use app\common\model\User;
use app\common\model\VisitLog;
use Yii;

/**
 * 商城统计：仪表盘汇总 / 日汇总落库
 */
final class StatService
{
    /** 仪表盘总览 */
    public static function dashboard(int $tenantId): array
    {
        $db = Yii::$app->db;
        $todayStart = strtotime('today');
        $yesterdayStart = $todayStart - 86400;

        $scalar = static function (string $sql, array $params = []) use ($db): float {
            return (float)$db->createCommand($sql)->bindValues($params)->queryScalar();
        };

        return [
            'today' => [
                'newUsers' => (int)$scalar(
                    'SELECT COUNT(*) FROM {{%user}} WHERE tenant_id = :t AND created_at >= :s',
                    [':t' => $tenantId, ':s' => $todayStart]
                ),
                'orderCount' => (int)$scalar(
                    'SELECT COUNT(*) FROM {{%order}} WHERE tenant_id = :t AND created_at >= :s',
                    [':t' => $tenantId, ':s' => $todayStart]
                ),
                'payCount' => (int)$scalar(
                    'SELECT COUNT(*) FROM {{%order}} WHERE tenant_id = :t AND paid_at >= :s',
                    [':t' => $tenantId, ':s' => $todayStart]
                ),
                'payAmount' => $scalar(
                    'SELECT IFNULL(SUM(pay_amount), 0) FROM {{%order}} WHERE tenant_id = :t AND paid_at >= :s',
                    [':t' => $tenantId, ':s' => $todayStart]
                ),
                'visitCount' => (int)$scalar(
                    'SELECT COUNT(*) FROM {{%visit_log}} WHERE tenant_id = :t AND created_at >= :s',
                    [':t' => $tenantId, ':s' => $todayStart]
                ),
            ],
            'yesterday' => [
                'orderCount' => (int)$scalar(
                    'SELECT COUNT(*) FROM {{%order}} WHERE tenant_id = :t AND created_at >= :s AND created_at < :e',
                    [':t' => $tenantId, ':s' => $yesterdayStart, ':e' => $todayStart]
                ),
                'payAmount' => $scalar(
                    'SELECT IFNULL(SUM(pay_amount), 0) FROM {{%order}} WHERE tenant_id = :t AND paid_at >= :s AND paid_at < :e',
                    [':t' => $tenantId, ':s' => $yesterdayStart, ':e' => $todayStart]
                ),
            ],
            'total' => [
                'users' => (int)User::find()->where(['tenant_id' => $tenantId])->count(),
                'goodsOnSale' => (int)$scalar(
                    "SELECT COUNT(*) FROM {{%goods}} WHERE tenant_id = :t AND status = 1",
                    [':t' => $tenantId]
                ),
                'pendingDelivery' => (int)$scalar(
                    'SELECT COUNT(*) FROM {{%order}} WHERE tenant_id = :t AND status = 20',
                    [':t' => $tenantId]
                ),
                'refundOpen' => (int)$scalar(
                    'SELECT COUNT(*) FROM {{%order_refund}} WHERE tenant_id = :t AND status IN (10, 20, 40)',
                    [':t' => $tenantId]
                ),
            ],
        ];
    }

    /** 近 N 天走势 */
    public static function trend(int $tenantId, int $days = 14): array
    {
        $rows = DailyStat::find()
            ->where(['tenant_id' => $tenantId])
            ->andWhere(['>=', 'stat_date', date('Y-m-d', strtotime('-' . ($days - 1) . ' days'))])
            ->orderBy('stat_date')
            ->asArray()
            ->all();
        return $rows;
    }

    /** 汇总指定日期（控制台调用，默认昨天） */
    public static function summarizeDay(string $date = ''): void
    {
        $date = $date ?: date('Y-m-d', time() - 86400);
        $start = strtotime($date . ' 00:00:00');
        $end = $start + 86399;
        $db = Yii::$app->db;

        $tenants = $db->createCommand('SELECT id FROM {{%tenant}}')->queryColumn();
        foreach ($tenants as $tenantId) {
            $orderCount = (int)$db->createCommand(
                'SELECT COUNT(*) FROM {{%order}} WHERE tenant_id = :t AND created_at BETWEEN :s AND :e',
                [':t' => $tenantId, ':s' => $start, ':e' => $end]
            )->queryScalar();
            $orderAmount = (float)$db->createCommand(
                'SELECT IFNULL(SUM(pay_amount),0) FROM {{%order}} WHERE tenant_id = :t AND created_at BETWEEN :s AND :e',
                [':t' => $tenantId, ':s' => $start, ':e' => $end]
            )->queryScalar();
            $payCount = (int)$db->createCommand(
                'SELECT COUNT(*) FROM {{%order}} WHERE tenant_id = :t AND paid_at BETWEEN :s AND :e',
                [':t' => $tenantId, ':s' => $start, ':e' => $end]
            )->queryScalar();
            $payAmount = (float)$db->createCommand(
                'SELECT IFNULL(SUM(pay_amount),0) FROM {{%order}} WHERE tenant_id = :t AND paid_at BETWEEN :s AND :e',
                [':t' => $tenantId, ':s' => $start, ':e' => $end]
            )->queryScalar();
            $newUsers = (int)$db->createCommand(
                'SELECT COUNT(*) FROM {{%user}} WHERE tenant_id = :t AND created_at BETWEEN :s AND :e',
                [':t' => $tenantId, ':s' => $start, ':e' => $end]
            )->queryScalar();

            $stat = DailyStat::find()
                ->where(['tenant_id' => $tenantId, 'stat_date' => $date])
                ->one();
            if (!$stat) {
                $stat = new DailyStat();
                $stat->tenant_id = $tenantId;
                $stat->stat_date = $date;
            }
            $stat->new_users = $newUsers;
            $stat->order_count = $orderCount;
            $stat->order_amount = (string)$orderAmount;
            $stat->pay_count = $payCount;
            $stat->pay_amount = (string)$payAmount;
            $stat->save(false);
        }
    }

    /** 记录访问（用户端首页/登录时） */
    public static function trackVisit(int $tenantId, int $userId, string $channel, string $path): void
    {
        $log = new VisitLog();
        $log->tenant_id = $tenantId;
        $log->user_id = $userId;
        $log->channel = $channel;
        $log->path = mb_substr($path, 0, 255);
        $log->ip = (string)(Yii::$app->request->getUserIP() ?? '');
        $log->save(false);
    }
}
