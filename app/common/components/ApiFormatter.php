<?php

declare(strict_types=1);

namespace app\common\components;

use yii\web\JsonResponseFormatter;

/**
 * JSON 输出格式化（数据包封由控制器与错误处理器自行完成）
 */
class ApiFormatter extends JsonResponseFormatter
{
    public $encodeOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
}
