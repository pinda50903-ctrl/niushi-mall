<?php

declare(strict_types=1);

namespace app\common\components;

use yii\web\ErrorHandler;
use yii\web\HttpException;

/**
 * 统一以 HTTP 200 输出 {code, message, data} 包封，错误码语义放在 code 里
 */
class ApiErrorHandler extends ErrorHandler
{
    protected function renderException($exception): void
    {
        $response = \Yii::$app->getResponse();
        $response->format = \yii\web\Response::FORMAT_JSON;

        if ($exception instanceof ApiException) {
            $code = $exception->getCode() ?: ApiException::VALIDATE;
            $message = $exception->getMessage();
        } elseif ($exception instanceof HttpException) {
            $map = [401 => ApiException::UNAUTHORIZED, 403 => ApiException::FORBIDDEN, 404 => ApiException::NOT_FOUND];
            $code = $map[$exception->statusCode] ?? ApiException::VALIDATE;
            $message = $exception->getMessage() ?: '请求错误';
        } else {
            $code = ApiException::SERVER_ERROR;
            $message = YII_DEBUG ? $exception->getMessage() : '服务器开小差了，请稍后再试';
        }

        $response->setStatusCode(200);
        $response->data = [
            'code' => $code,
            'message' => $message,
            'data' => null,
        ];

        if (YII_DEBUG) {
            $response->data['_debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode('#15', $exception->getTraceAsString())[0],
            ];
        }

        $response->send();
    }
}
