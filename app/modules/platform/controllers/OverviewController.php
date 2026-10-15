<?php

declare(strict_types=1);

namespace app\modules\platform\controllers;

use app\common\components\AccessIdentity;
use app\common\components\BearerToken;
use app\common\components\RestController;
use Yii;

/**
 * 平台数据总览
 */
final class OverviewController extends RestController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['auth'] = [
            'class' => BearerToken::class,
            'types' => [AccessIdentity::TYPE_PLATFORM],
        ];
        return $behaviors;
    }

    public function actionIndex(): \yii\web\Response
    {
        $db = Yii::$app->db;
        $today = strtotime('today');
        $scalar = static function (string $sql, array $params = []) use ($db): float {
            return (float)$db->createCommand($sql)->bindValues($params)->queryScalar();
        };

        return $this->ok([
            'tenants' => [
                'total' => (int)$scalar('SELECT COUNT(*) FROM {{%tenant}}'),
                'active' => (int)$scalar('SELECT COUNT(*) FROM {{%tenant}} WHERE status = 1'),
            ],
            'todayGmv' => $scalar(
                'SELECT IFNULL(SUM(pay_amount),0) FROM {{%order}} WHERE paid_at >= :s',
                [':s' => $today]
            ),
            'todayOrders' => (int)$scalar(
                'SELECT COUNT(*) FROM {{%order}} WHERE created_at >= :s',
                [':s' => $today]
            ),
            'totalUsers' => (int)$scalar('SELECT COUNT(*) FROM {{%user}}'),
            'plugins' => [
                'installed' => (int)$scalar('SELECT COUNT(*) FROM {{%plugin}}'),
                'online' => (int)$scalar('SELECT COUNT(*) FROM {{%plugin}} WHERE status = 1'),
            ],
            'recentTenants' => (new \yii\db\Query())
                ->from('{{%tenant}}')
                ->orderBy('id DESC')
                ->limit(5)
                ->all(),
            'topTenants' => (new \yii\db\Query())
                ->select(['t.id', 't.name', 't.slug', 'COUNT(o.id) AS order_count', 'IFNULL(SUM(o.pay_amount),0) AS pay_amount'])
                ->from(['t' => '{{%tenant}}'])
                ->leftJoin(['o' => '{{%order}}'], 'o.tenant_id = t.id AND o.paid_at >= :s')
                ->groupBy('t.id')
                ->orderBy('pay_amount DESC')
                ->limit(5)
                ->params([':s' => strtotime('-30 days')])
                ->all(),
        ]);
    }
}
