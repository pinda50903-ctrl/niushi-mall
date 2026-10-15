<?php

declare(strict_types=1);

namespace app\common\services;

use app\common\components\ApiException;
use app\common\model\Tenant;
use Yii;

/**
 * 租户上下文：用户端请求通过 X-Mall-Id 头或 tenantId 参数定位租户
 */
final class TenantContext
{
    private static ?Tenant $tenant = null;
    private static bool $resolved = false;

    public static function resolve(): Tenant
    {
        if (self::$resolved) {
            if (self::$tenant === null) {
                throw new ApiException('未指定商城', ApiException::NOT_FOUND);
            }
            return self::$tenant;
        }
        self::$resolved = true;

        $request = Yii::$app->request;
        $raw = (string)($request->headers->get('X-Mall-Id', '')
            ?: $request->get('tenantId', $request->post('tenantId', '')));
        if ($raw === '') {
            // 回退：租户管理员身份自带所属商城
            $identity = Yii::$app->user->getIdentity();
            if ($identity instanceof \app\common\components\AccessIdentity
                && $identity->isTenantAdmin()
                && (int)$identity->tenantId > 0) {
                $raw = (string)(int)$identity->tenantId;
            }
        }
        if ($raw === '') {
            throw new ApiException('未指定商城', ApiException::NOT_FOUND);
        }
        $tenant = ctype_digit($raw)
            ? Tenant::find()->where(['id' => (int)$raw])->one()
            : Tenant::find()->where(['slug' => $raw])->one();
        if (!$tenant || (int)$tenant->status !== 1) {
            throw new ApiException('商城不存在或已停用', ApiException::NOT_FOUND);
        }
        if ((int)$tenant->expire_at > 0 && (int)$tenant->expire_at < time()) {
            throw new ApiException('商城服务已到期', ApiException::FORBIDDEN);
        }
        return self::$tenant = $tenant;
    }

    public static function id(): int
    {
        return self::resolve()->id;
    }

    public static function forget(): void
    {
        self::$tenant = null;
        self::$resolved = false;
    }
}
