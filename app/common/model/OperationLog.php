<?php

declare(strict_types=1);

namespace app\common\model;

/** 后台操作日志 */
class OperationLog extends \app\common\db\ActiveRecord
{
    public function behaviors(): array
    {
        return [];
    }
}
