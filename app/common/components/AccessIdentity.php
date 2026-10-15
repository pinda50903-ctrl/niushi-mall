<?php

declare(strict_types=1);

namespace app\common\components;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * 访问身份：包装 JWT 载荷与对应的数据库记录
 */
class AccessIdentity implements IdentityInterface
{
    public const TYPE_PLATFORM = 'admin';
    public const TYPE_TENANT = 'tenant';
    public const TYPE_USER = 'user';

    public string $type;
    public int $accountId;
    public ?int $tenantId;
    public ActiveRecord $record;

    public static function fromPayload(array $payload, ActiveRecord $record): self
    {
        $identity = new self();
        $identity->type = $payload['typ'];
        $identity->accountId = $payload['aid'];
        $identity->tenantId = $payload['tid'];
        $identity->record = $record;
        return $identity;
    }

    /** @inheritdoc */
    public static function findIdentity($id): ?IdentityInterface
    {
        return null; // 无会话，仅凭 token 鉴权
    }

    /** @inheritdoc */
    public static function findIdentityByAccessToken($token, $type = null): ?IdentityInterface
    {
        return null; // 鉴权统一走 BearerToken 过滤器
    }

    /** @inheritdoc */
    public function getId(): ?int
    {
        return $this->accountId;
    }

    /** @inheritdoc */
    public function getAuthKey(): ?string
    {
        return null;
    }

    /** @inheritdoc */
    public function validateAuthKey($authKey): ?bool
    {
        return null;
    }

    public function isPlatform(): bool
    {
        return $this->type === self::TYPE_PLATFORM;
    }

    public function isTenantAdmin(): bool
    {
        return $this->type === self::TYPE_TENANT;
    }

    public function isUser(): bool
    {
        return $this->type === self::TYPE_USER;
    }

    /**
     * 是否拥有指定权限点（tenant/platform 角色权限树，user 恒为 false）
     */
    public function can(string $permission): bool
    {
        if ($this->type === self::TYPE_USER) {
            return false;
        }
        $roleModel = $this->type === self::TYPE_PLATFORM
            ? \app\common\model\PlatformRole::class
            : \app\common\model\TenantRole::class;
        $role = $roleModel::find()
            ->where(['id' => $this->record->getAttribute('role_id') ?: 0])
            ->one();
        if (!$role) {
            return false;
        }
        if ((int)$role->is_super === 1) {
            return true;
        }
        $permissions = json_decode((string)$role->permissions, true) ?: [];
        return in_array($permission, $permissions, true);
    }
}
