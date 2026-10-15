<?php

declare(strict_types=1);

namespace app\plugins\groupon\controllers;

use app\common\components\ApiException;
use app\plugins\PluginRestController;
use app\plugins\groupon\models\GrouponActivity;
use app\plugins\groupon\models\GrouponMember;
use app\plugins\groupon\models\GrouponTeam;
use Yii;

/**
 * 拼团用户端：开团 / 参团 / 团详情
 */
final class TeamController extends PluginRestController
{
    protected function pluginCode(): string
    {
        return 'groupon';
    }

    /** 进行中的拼团活动列表 */
    public function actionActivities(): \yii\web\Response
    {
        $query = GrouponActivity::find()
            ->where(['tenant_id' => $this->tenantId(), 'status' => 1])
            ->andWhere(['<=', 'start_at', time()])
            ->andWhere(['>=', 'end_at', time()])
            ->orderBy('id DESC');
        return $this->okList($query);
    }

    /** 活动详情（含进行中团队） */
    public function actionActivity(): \yii\web\Response
    {
        /** @var GrouponActivity|null $activity */
        $activity = GrouponActivity::find()
            ->where(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()])
            ->one();
        if (!$activity) {
            throw new ApiException('活动不存在', ApiException::NOT_FOUND);
        }
        $teams = GrouponTeam::find()
            ->where(['activity_id' => $activity->id, 'status' => 1])
            ->andWhere(['>', 'expire_at', time()])
            ->orderBy('id DESC')
            ->limit(10)
            ->all();
        return $this->ok(['activity' => $activity, 'openTeams' => $teams]);
    }

    /** 开团：以拼团价下单并建立团组 */
    public function actionLaunch(): \yii\web\Response
    {
        $userId = $this->currentUserId();
        $activityId = $this->intInput('activityId');
        /** @var GrouponActivity|null $activity */
        $activity = GrouponActivity::find()
            ->where(['id' => $activityId, 'tenant_id' => $this->tenantId(), 'status' => 1])
            ->one();
        if (!$activity || time() > (int)$activity->end_at || time() < (int)$activity->start_at) {
            throw new ApiException('拼团活动不存在或不在有效期', ApiException::NOT_FOUND);
        }
        if ((int)$activity->stock <= 0) {
            throw new ApiException('拼团库存不足', ApiException::VALIDATE);
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            $input = $this->body();
            $input['goodsId'] = (int)$activity->goods_id;
            $input['num'] = 1;
            $order = \app\common\services\OrderService::submit($userId, $this->tenantId(), 'groupon', $input);
            \app\common\model\Order::updateAll(
                ['pay_amount' => (float)$activity->group_price, 'deduction_amount' => (float)$order->goods_amount - (float)$activity->group_price],
                ['id' => $order->id]
            );

            $team = new GrouponTeam();
            $team->tenant_id = $this->tenantId();
            $team->activity_id = $activityId;
            $team->order_id = (int)$order->id;
            $team->leader_user_id = $userId;
            $team->need_count = (int)$activity->group_size;
            $team->joined_count = 1;
            $team->expire_at = time() + (int)$activity->time_limit_hours * 3600;
            $team->status = 1;
            $team->save(false);

            $member = new GrouponMember();
            $member->tenant_id = $this->tenantId();
            $member->team_id = (int)$team->id;
            $member->user_id = $userId;
            $member->order_id = (int)$order->id;
            $member->is_leader = 1;
            $member->save(false);

            GrouponActivity::updateAllCounters(['stock' => -1], ['id' => $activityId]);
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
        return $this->ok(['teamId' => (int)$team->id, 'orderId' => (int)$order->id], '开团成功，请完成支付');
    }

    /** 参团：以拼团价下单加入既有团 */
    public function actionJoin(): \yii\web\Response
    {
        $userId = $this->currentUserId();
        $teamId = $this->intInput('teamId');
        /** @var GrouponTeam|null $team */
        $team = GrouponTeam::find()
            ->where(['id' => $teamId, 'tenant_id' => $this->tenantId(), 'status' => 1])
            ->one();
        if (!$team) {
            throw new ApiException('拼团不存在或已结束', ApiException::NOT_FOUND);
        }
        if ((int)$team->expire_at < time()) {
            throw new ApiException('该团已超时', ApiException::VALIDATE);
        }
        if ((int)$team->joined_count >= (int)$team->need_count) {
            throw new ApiException('该团已满员', ApiException::VALIDATE);
        }
        if (GrouponMember::find()->where(['team_id' => $teamId, 'user_id' => $userId])->exists()) {
            throw new ApiException('你已在该团中', ApiException::VALIDATE);
        }
        /** @var GrouponActivity|null $activity */
        $activity = GrouponActivity::find()->where(['id' => $team->activity_id])->one();

        $tx = Yii::$app->db->beginTransaction();
        try {
            $input = $this->body();
            $input['goodsId'] = (int)$activity->goods_id;
            $input['num'] = 1;
            $order = \app\common\services\OrderService::submit($userId, $this->tenantId(), 'groupon', $input);
            \app\common\model\Order::updateAll(
                ['pay_amount' => (float)$activity->group_price, 'deduction_amount' => (float)$order->goods_amount - (float)$activity->group_price],
                ['id' => $order->id]
            );

            $member = new GrouponMember();
            $member->tenant_id = $this->tenantId();
            $member->team_id = $teamId;
            $member->user_id = $userId;
            $member->order_id = (int)$order->id;
            $member->save(false);

            GrouponTeam::updateAllCounters(['joined_count' => 1], ['id' => $teamId]);
            if ((int)$team->joined_count + 1 >= (int)$team->need_count) {
                GrouponTeam::updateAll(['status' => 2], ['id' => $teamId]);
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
        return $this->ok(['teamId' => $teamId, 'orderId' => (int)$order->id], '参团成功，请完成支付');
    }

    /** 团详情与成员 */
    public function actionView(): \yii\web\Response
    {
        $teamId = $this->intInput('teamId');
        /** @var GrouponTeam|null $team */
        $team = GrouponTeam::find()
            ->where(['id' => $teamId, 'tenant_id' => $this->tenantId()])
            ->one();
        if (!$team) {
            throw new ApiException('拼团不存在', ApiException::NOT_FOUND);
        }
        $members = GrouponMember::find()->where(['team_id' => $teamId])->all();
        $userIds = array_map(static fn ($m) => (int)$m->user_id, $members);
        $users = $userIds === [] ? [] : \app\common\model\User::find()
            ->select('id, nickname, avatar')
            ->where(['id' => $userIds])
            ->asArray()->all();
        return $this->ok([
            'team' => $team,
            'members' => $members,
            'users' => array_column($users, null, 'id'),
        ]);
    }
}
