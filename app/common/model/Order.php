<?php

declare(strict_types=1);

namespace app\common\model;

/** 订单 */
class Order extends \app\common\db\ActiveRecord
{
    public const STATUS_PENDING_PAY = 10;
    public const STATUS_PENDING_DELIVERY = 20;
    public const STATUS_DELIVERED = 30;
    public const STATUS_FINISHED = 40;
    public const STATUS_CLOSED = 50;

    public const STATUS_LABELS = [
        self::STATUS_PENDING_PAY => '待付款',
        self::STATUS_PENDING_DELIVERY => '待发货',
        self::STATUS_DELIVERED => '待收货',
        self::STATUS_FINISHED => '已完成',
        self::STATUS_CLOSED => '已关闭',
    ];

    public function fields(): array
    {
        $fields = parent::fields();
        $fields['status_label'] = fn () => self::STATUS_LABELS[(int) $this->status] ?? '未知';
        return $fields;
    }
}
