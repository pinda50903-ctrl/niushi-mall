<?php

declare(strict_types=1);

namespace app\common\model;

/** 浏览足迹 */
class BrowseLog extends \app\common\db\ActiveRecord
{
    public function behaviors(): array
    {
        return [];
    }
}
