<?php

declare(strict_types=1);

namespace app\common\events;

use app\common\model\Order;

/**
 * 订单事件：携带订单对象
 *
 * 注意：不能把订单放进 parent::$data —— Component::trigger 会用 on() 附带的
 * 监听器数据覆盖 $event->data，自定义属性则不受影响。
 */
class OrderEvent extends \yii\base\Event
{
    public ?Order $order = null;

    public function __construct(?Order $order = null)
    {
        parent::__construct();
        $this->order = $order;
    }
}
