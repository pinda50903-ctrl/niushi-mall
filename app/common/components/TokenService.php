<?php

declare(strict_types=1);

namespace app\common\components;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JWT 令牌签发与解析
 *
 * token 载荷：
 *   typ  身份类型 admin=平台总部 / tenant=商城管理端 / user=商城用户
 *   aid  对应记录 id
 *   tid  租户 id（admin 类型为 null）
 *   exp  过期时间
 */
class TokenService
{
    private static function secret(): string
    {
        return (string)require \Yii::getAlias('@app/config/jwt-key.php');
    }

    private static function key(): Key
    {
        return new Key(self::secret(), 'HS256');
    }

    public static function issue(string $type, int $id, ?int $tenantId = null, int $ttl = 86400 * 7): string
    {
        $now = time();
        return JWT::encode([
            'typ' => $type,
            'aid' => $id,
            'tid' => $tenantId,
            'iat' => $now,
            'exp' => $now + $ttl,
            'jti' => bin2hex(random_bytes(8)),
        ], self::secret(), 'HS256');
    }

    /**
     * @return array{typ: string, aid: int, tid: ?int}
     */
    public static function parse(string $token): array
    {
        $payload = (array)JWT::decode($token, self::key());
        if (!isset($payload['typ'], $payload['aid'])) {
            throw new ApiException('无效的访问令牌', ApiException::UNAUTHORIZED);
        }
        if ((int)($payload['exp'] ?? 0) < time()) {
            throw new ApiException('登录已过期，请重新登录', ApiException::UNAUTHORIZED);
        }
        return [
            'typ' => (string)$payload['typ'],
            'aid' => (int)$payload['aid'],
            'tid' => isset($payload['tid']) && $payload['tid'] !== null ? (int)$payload['tid'] : null,
        ];
    }
}
