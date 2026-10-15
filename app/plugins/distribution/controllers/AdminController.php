<?php

declare(strict_types=1);

namespace app\plugins\distribution\controllers;

use app\common\components\ApiException;
use app\common\services\WalletService;
use app\plugins\PluginAdminController;
use app\plugins\distribution\models\Commission;
use app\plugins\distribution\models\Relation;
use app\plugins\distribution\models\Withdraw;
use app\plugins\distribution\services\CommissionService;
use Yii;

/**
 * 分销管理端：配置 / 关系 / 佣金 / 提现审核
 */
final class AdminController extends PluginAdminController
{
    protected function pluginCode(): string
    {
        return 'distribution';
    }

    /** 读取插件配置 */
    public function actionConfig(): \yii\web\Response
    {
        return $this->ok([
            'config' => CommissionService::config($this->tenantId()),
        ]);
    }

    /** 保存插件配置 */
    public function actionSaveConfig(): \yii\web\Response
    {
        $input = (array)($this->input('config') ?? []);
        $value = [
            'first_ratio' => max(0, min(50, (float)($input['first_ratio'] ?? 10))),
            'second_ratio' => max(0, min(50, (float)($input['second_ratio'] ?? 5))),
            'min_withdraw' => max(1, (float)($input['min_withdraw'] ?? 10)),
        ];
        \app\common\services\PluginSettingService::save($this->pluginCode(), $value, $this->tenantId());
        return $this->ok(null, '分销配置已保存');
    }

    /** 推广关系列表 */
    public function actionRelations(): \yii\web\Response
    {
        $query = Relation::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('id DESC');
        return $this->okList($query, static function (Relation $relation): array {
            $users = \app\common\model\User::find()
                ->select('id, nickname')
                ->where(['id' => [(int)$relation->user_id, (int)$relation->parent_id]])
                ->asArray()->indexBy('id')->all();
            return [
                'id' => (int)$relation->id,
                'userId' => (int)$relation->user_id,
                'nickname' => $users[(int)$relation->user_id]['nickname'] ?? ('用户#' . $relation->user_id),
                'parentId' => (int)$relation->parent_id,
                'parentNickname' => $users[(int)$relation->parent_id]['nickname'] ?? ('用户#' . $relation->parent_id),
                'bindAt' => (int)$relation->bind_at,
            ];
        });
    }

    /** 佣金流水列表 */
    public function actionCommissions(): \yii\web\Response
    {
        $query = Commission::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('id DESC');
        return $this->okList($query, static function (Commission $row): array {
            return [
                'id' => (int)$row->id,
                'userId' => (int)$row->user_id,
                'buyerId' => (int)$row->buyer_id,
                'orderNo' => (string)$row->order_no,
                'level' => (int)$row->level,
                'amount' => (float)$row->amount,
                'status' => (int)$row->status,
                'statusText' => [0 => '待结算', 1 => '已入账', 2 => '已失效'][(int)$row->status] ?? '未知',
                'settleAt' => (int)$row->settle_at,
                'createdAt' => (int)$row->created_at,
            ];
        });
    }

    /** 提现单列表 */
    public function actionWithdraws(): \yii\web\Response
    {
        $query = Withdraw::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('id DESC');
        return $this->okList($query, static fn (Withdraw $row): array => [
            'id' => (int)$row->id,
            'userId' => (int)$row->user_id,
            'amount' => (float)$row->amount,
            'status' => (int)$row->status,
            'statusText' => [0 => '待审核', 1 => '已打款', 2 => '已驳回'][(int)$row->status] ?? '未知',
            'remark' => (string)$row->remark,
            'handledAt' => (int)$row->handled_at,
            'createdAt' => (int)$row->created_at,
        ]);
    }

    /** 审核提现：agree=1 打款入余额 / 0 驳回 */
    public function actionAuditWithdraw(): \yii\web\Response
    {
        $id = $this->intInput('id');
        $agree = $this->intInput('agree', 1) === 1;
        /** @var Withdraw|null $withdraw */
        $withdraw = Withdraw::find()
            ->where(['id' => $id, 'tenant_id' => $this->tenantId(), 'status' => Withdraw::STATUS_PENDING])
            ->one();
        if (!$withdraw) {
            throw new ApiException('提现单不存在或已处理', ApiException::NOT_FOUND);
        }
        $tx = Yii::$app->db->beginTransaction();
        try {
            if ($agree) {
                $withdraw->status = Withdraw::STATUS_PAID;
                $withdraw->remark = '已打款至钱包余额';
                WalletService::changeBalance(
                    (int)$withdraw->user_id,
                    (float)$withdraw->amount,
                    1,
                    'distribution_withdraw',
                    'W' . (int)$withdraw->id,
                    '分销佣金提现打款'
                );
            } else {
                $withdraw->status = Withdraw::STATUS_REFUSED;
                $withdraw->remark = $this->strInput('remark', '不符合提现要求');
            }
            $withdraw->handled_at = time();
            $withdraw->save(false);
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
        return $this->ok(null, $agree ? '已打款' : '已驳回');
    }
}
