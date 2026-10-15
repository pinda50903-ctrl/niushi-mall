<?php

declare(strict_types=1);

namespace app\common\model;

/** 售后单 */
class OrderRefund extends \app\common\db\ActiveRecord
{
    public const TYPE_REFUND_ONLY = 0;
    public const TYPE_RETURN = 1;

    public const STATUS_APPLYING = 10;
    public const STATUS_APPROVED = 20;
    public const STATUS_REFUSED = 30;
    public const STATUS_RETURNING = 40;
    public const STATUS_REFUNDED = 50;
    public const STATUS_CLOSED = 60;

    public const STATUS_LABELS = [
        self::STATUS_APPLYING => '待处理',
        self::STATUS_APPROVED => '已同意待退货',
        self::STATUS_REFUSED => '已拒绝',
        self::STATUS_RETURNING => '待收退货',
        self::STATUS_REFUNDED => '已退款',
        self::STATUS_CLOSED => '已关闭',
    ];

    public function fields(): array
    {
        $fields = parent::fields();
        $fields['status_label'] = fn () => self::STATUS_LABELS[(int) $this->status] ?? '未知';
        return $fields;
    }
}
