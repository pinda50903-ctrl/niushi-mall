<?php

declare(strict_types=1);

namespace app\common\components;

/**
 * 业务异常：抛出后由 ApiErrorHandler 转为统一响应包封
 *
 * 错误码约定：
 *   0      成功
 *   40000  参数/校验错误
 *   40100  未登录或凭证失效
 *   40300  无权限
 *   40400  资源不存在
 *   50000  服务器内部错误
 *   4xxxx  其余业务自定义码
 */
class ApiException extends \yii\base\Exception
{
    public const VALIDATE = 40000;
    public const UNAUTHORIZED = 40100;
    public const FORBIDDEN = 40300;
    public const NOT_FOUND = 40400;
    public const SERVER_ERROR = 50000;

    public function __construct(string $message, int $code = self::VALIDATE, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
