<?php

declare(strict_types=1);

namespace app\common\components;

use yii\filters\auth\AuthMethod;
use yii\web\Request;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

/**
 * Bearer JWT 鉴权过滤器
 *
 * 用法（控制器 behaviors）：
 *   ['class' => BearerToken::class, 'types' => [AccessIdentity::TYPE_TENANT]]
 */
class BearerToken extends AuthMethod
{
    /** 允许的身份类型，空数组表示任意类型 */
    public array $types = [];

    public function authenticate($user, $request, $response): ?AccessIdentity
    {
        $identity = self::resolveIdentity($request);
        if ($identity === null) {
            return null; // 走 challenge（可选鉴权场景由控制器决定）
        }
        if ($this->types !== [] && !in_array($identity->type, $this->types, true)) {
            throw new UnauthorizedHttpException('身份类型不符合，请使用正确的账号登录');
        }
        $user->setIdentity($identity);
        return $identity;
    }

    /**
     * 从请求中解析身份（无 token / 无效 token 返回 null，无效已登录 token 抛 401）
     */
    public static function resolveIdentity(Request $request): ?AccessIdentity
    {
        $token = self::extractToken($request);
        if ($token === '') {
            return null;
        }
        try {
            $payload = TokenService::parse($token);
        } catch (\Throwable $e) {
            throw new UnauthorizedHttpException($e instanceof ApiException ? $e->getMessage() : '无效的访问令牌');
        }
        return self::loadIdentity($payload);
    }

    public function challenge($response): void
    {
        // 保持 HTTP 200，由统一包封表达 40100
        $response->setStatusCode(200);
    }

    private static function extractToken(Request $request): string
    {
        $header = (string)$request->headers->get('Authorization', '');
        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            return $m[1];
        }
        $header = (string)$request->headers->get('token', '');
        if ($header !== '') {
            return $header;
        }
        return (string)($request->post('token', $request->get('token', '')));
    }

    private static function loadIdentity(array $payload): ?AccessIdentity
    {
        switch ($payload['typ']) {
            case AccessIdentity::TYPE_PLATFORM:
                $record = \app\common\model\PlatformAdmin::find()
                    ->where(['id' => $payload['aid'], 'status' => 1])->one();
                break;
            case AccessIdentity::TYPE_TENANT:
                $record = \app\common\model\TenantAdmin::find()
                    ->where(['id' => $payload['aid'], 'status' => 1])->one();
                break;
            case AccessIdentity::TYPE_USER:
                if ($payload['aid'] === 0) {
                    // 游客身份：可浏览，下单等操作由业务层再校验
                    $guest = new \app\common\model\User();
                    $guest->id = 0;
                    return AccessIdentity::fromPayload($payload, $guest);
                }
                $record = \app\common\model\User::find()
                    ->where(['id' => $payload['aid'], 'status' => 1])->one();
                break;
            default:
                return null;
        }
        if (!$record) {
            return null;
        }
        return AccessIdentity::fromPayload($payload, $record);
    }
}
