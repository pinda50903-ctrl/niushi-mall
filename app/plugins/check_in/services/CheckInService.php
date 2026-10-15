<?php

declare(strict_types=1);

namespace app\plugins\check_in\services;

use app\common\components\ApiException;
use app\common\services\WalletService;
use app\plugins\check_in\models\CheckInRecord;
use Yii;

/**
 * 签到：每日打卡、连续奖励、积分补签
 */
final class CheckInService
{
    public static function defaults(): array
    {
        return [
            'base_integral' => 5,    // 每日基础积分
            'seven_bonus' => 10,     // 连续每满 7 天额外奖励
            'thirty_bonus' => 50,    // 连续满 30 天额外奖励
            'makeup_cost' => 20,     // 补签消耗积分
        ];
    }

    public static function config(int $tenantId): array
    {
        $saved = \app\common\services\PluginSettingService::get('check_in', $tenantId);
        return array_merge(self::defaults(), is_array($saved) ? $saved : []);
    }

    private static function ymd(int $offsetDays = 0): int
    {
        return (int)date('Ymd', strtotime($offsetDays . ' days'));
    }

    /** 查询某天记录 */
    public static function recordOf(int $tenantId, int $userId, int $ymd): ?CheckInRecord
    {
        /** @var CheckInRecord|null $record */
        $record = CheckInRecord::find()
            ->where(['tenant_id' => $tenantId, 'user_id' => $userId, 'ymd' => $ymd])
            ->one();
        return $record;
    }

    /** 今日签到 */
    public static function sign(int $tenantId, int $userId): array
    {
        if ($userId <= 0) {
            throw new ApiException('请先登录', ApiException::UNAUTHORIZED);
        }
        $today = self::ymd();
        if (self::recordOf($tenantId, $userId, $today)) {
            throw new ApiException('今天已经签到过了', ApiException::VALIDATE);
        }
        $yesterday = self::recordOf($tenantId, $userId, self::ymd(-1));
        $continuous = $yesterday ? (int)$yesterday->continuous + 1 : 1;

        $config = self::config($tenantId);
        $reward = (int)$config['base_integral'];
        $memo = '每日签到';
        if ($continuous > 0 && $continuous % 7 === 0) {
            $reward += (int)$config['seven_bonus'];
            $memo = '连续签到 ' . $continuous . ' 天奖励';
        }
        if ($continuous === 30) {
            $reward += (int)$config['thirty_bonus'];
            $memo = '连续签到满 30 天大奖';
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            $record = new CheckInRecord();
            $record->tenant_id = $tenantId;
            $record->user_id = $userId;
            $record->ymd = $today;
            $record->continuous = $continuous;
            $record->reward_integral = $reward;
            $record->is_makeup = 0;
            $record->created_at = time();
            $record->save(false);
            if ($reward > 0) {
                WalletService::changeIntegral($userId, $reward, 1, 'check_in', (string)$today, $memo);
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
        return [
            'ymd' => $today,
            'continuous' => $continuous,
            'rewardIntegral' => $reward,
        ];
    }

    /** 补签（本月内缺勤日，扣积分，不计连续） */
    public static function makeup(int $tenantId, int $userId, int $ymd): array
    {
        if ($userId <= 0) {
            throw new ApiException('请先登录', ApiException::UNAUTHORIZED);
        }
        $today = self::ymd();
        $monthPrefix = (int)date('Ym');
        if ($ymd >= $today) {
            throw new ApiException('只能补签过去的日期', ApiException::VALIDATE);
        }
        if ((int)floor($ymd / 100) !== $monthPrefix) {
            throw new ApiException('只能补签本月日期', ApiException::VALIDATE);
        }
        if (self::recordOf($tenantId, $userId, $ymd)) {
            throw new ApiException('该日期已有签到记录', ApiException::VALIDATE);
        }
        $config = self::config($tenantId);
        $cost = (int)$config['makeup_cost'];

        $tx = Yii::$app->db->beginTransaction();
        try {
            $record = new CheckInRecord();
            $record->tenant_id = $tenantId;
            $record->user_id = $userId;
            $record->ymd = $ymd;
            $record->continuous = 0;
            $record->reward_integral = 0;
            $record->is_makeup = 1;
            $record->created_at = time();
            $record->save(false);
            if ($cost > 0) {
                WalletService::changeIntegral($userId, $cost, -1, 'check_in_makeup', (string)$ymd, '补签扣除积分');
            }
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
        return ['ymd' => $ymd, 'costIntegral' => $cost];
    }

    /** 签到状态 + 指定月日历 */
    public static function status(int $tenantId, int $userId, int $month = 0): array
    {
        $today = self::ymd();
        $todayRecord = self::recordOf($tenantId, $userId, $today);
        $continuous = $todayRecord
            ? (int)$todayRecord->continuous
            : (int)(self::recordOf($tenantId, $userId, self::ymd(-1))->continuous ?? 0);
        $totalDays = (int)CheckInRecord::find()
            ->where(['tenant_id' => $tenantId, 'user_id' => $userId])
            ->count();

        $month = $month > 0 ? $month : (int)date('Ym');
        $rows = CheckInRecord::find()
            ->select('ymd, continuous, reward_integral, is_makeup')
            ->where(['tenant_id' => $tenantId, 'user_id' => $userId])
            ->andWhere(['between', 'ymd', $month * 100 + 1, $month * 100 + 31])
            ->asArray()->all();

        return [
            'todaySigned' => (bool)$todayRecord,
            'continuous' => $continuous,
            'totalDays' => $totalDays,
            'month' => $month,
            'monthDays' => array_map(static fn (array $r): array => [
                'ymd' => (int)$r['ymd'],
                'continuous' => (int)$r['continuous'],
                'rewardIntegral' => (int)$r['reward_integral'],
                'isMakeup' => (int)$r['is_makeup'] === 1,
            ], $rows),
            'config' => self::config($tenantId),
        ];
    }
}
