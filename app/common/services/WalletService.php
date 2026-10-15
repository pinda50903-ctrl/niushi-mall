<?php

declare(strict_types=1);

namespace app\common\services;

use app\common\components\ApiException;
use app\common\model\BalanceLog;
use app\common\model\IntegralLog;
use app\common\model\User;
use Yii;

/**
 * 用户钱包：余额/积分变动 + 流水（事务保护）
 */
final class WalletService
{
    private static function lockUser(int $userId): ?User
    {
        $query = User::find()->where(['id' => $userId]);
        return Yii::$app->db->driverName === 'mysql' ? $query->forUpdate()->one() : $query->one();
    }

    /**
     * 变更余额
     * @param int $direction 1 增加 -1 扣减
     */
    public static function changeBalance(int $userId, float $amount, int $direction, string $scene, string $relatedNo = '', string $memo = ''): void
    {
        if ($amount <= 0) {
            return;
        }
        $tx = Yii::$app->db->beginTransaction();
        try {
            /** @var User|null $user */
            $user = self::lockUser($userId);
            if (!$user) {
                throw new ApiException('用户不存在', ApiException::NOT_FOUND);
            }
            if ($direction < 0 && (float)$user->balance < $amount) {
                throw new ApiException('余额不足', ApiException::VALIDATE);
            }
            $user->balance = round((float)$user->balance + $direction * $amount, 2);
            if (!$user->save(false)) {
                throw new ApiException('余额更新失败', ApiException::SERVER_ERROR);
            }
            $log = new BalanceLog();
            $log->tenant_id = (int)$user->tenant_id;
            $log->user_id = $userId;
            $log->scene = $scene;
            $log->direction = $direction > 0 ? 1 : 2; // 1 收入 2 支出
            $log->amount = $amount;
            $log->balance_after = (string)$user->balance;
            $log->related_no = $relatedNo;
            $log->remark = $memo;
            $log->save(false);
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    /**
     * 变更积分
     */
    public static function changeIntegral(int $userId, int $amount, int $direction, string $scene, string $relatedNo = '', string $memo = ''): void
    {
        if ($amount <= 0) {
            return;
        }
        $tx = Yii::$app->db->beginTransaction();
        try {
            /** @var User|null $user */
            $user = self::lockUser($userId);
            if (!$user) {
                throw new ApiException('用户不存在', ApiException::NOT_FOUND);
            }
            if ($direction < 0 && (int)$user->integral < $amount) {
                throw new ApiException('积分不足', ApiException::VALIDATE);
            }
            $user->integral = (int)$user->integral + $direction * $amount;
            if (!$user->save(false)) {
                throw new ApiException('积分更新失败', ApiException::SERVER_ERROR);
            }
            $log = new IntegralLog();
            $log->tenant_id = (int)$user->tenant_id;
            $log->user_id = $userId;
            $log->scene = $scene;
            $log->direction = $direction > 0 ? 1 : 2;
            $log->num = $amount;
            $log->balance_after = (string)$user->integral;
            $log->related_no = $relatedNo;
            $log->remark = $memo;
            $log->save(false);
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }
}
