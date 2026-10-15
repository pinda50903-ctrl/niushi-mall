<?php

declare(strict_types=1);

namespace app\common\model;

/** 访问日志 */
class VisitLog extends \app\common\db\ActiveRecord
{
    public function behaviors(): array
    {
        return [];
    }
}
