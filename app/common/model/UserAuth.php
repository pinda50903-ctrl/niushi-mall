<?php

declare(strict_types=1);

namespace app\common\model;

/** 用户第三方身份 */
class UserAuth extends \app\common\db\ActiveRecord
{
    public function behaviors(): array
    {
        return [];
    }
}
