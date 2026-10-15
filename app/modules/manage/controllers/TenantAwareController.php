<?php

declare(strict_types=1);

namespace app\modules\manage\controllers;

use app\common\components\AccessIdentity;
use app\common\components\ApiException;
use app\common\components\BearerToken;
use app\common\components\RestController;
use app\common\model\OperationLog;
use Yii;

/**
 * 商城管理端控制器基类：租户管理员鉴权 + 权限点
 *
 * 子类通过 $permission 声明所需权限点，超管与持有者方可访问。
 */
abstract class TenantAwareController extends RestController
{
    /** 本控制器所需权限点，如 'mall:goods' */
    protected string $permission = '';

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['auth'] = [
            'class' => BearerToken::class,
            'types' => [AccessIdentity::TYPE_TENANT],
        ];
        return $behaviors;
    }

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        /** @var AccessIdentity $identity */
        $identity = Yii::$app->user->getIdentity();
        if ($this->permission !== '' && !$identity->can($this->permission)) {
            throw new ApiException('没有操作权限', ApiException::FORBIDDEN);
        }
        return true;
    }

    /** 当前登录管理员所属租户 id */
    protected function tenantId(): int
    {
        /** @var AccessIdentity $identity */
        $identity = Yii::$app->user->getIdentity();
        if ($identity->tenantId === null) {
            throw new ApiException('账号未绑定商城', ApiException::FORBIDDEN);
        }
        return $identity->tenantId;
    }

    /** 操作日志 */
    protected function logOperation(string|array $payload = ''): void
    {
        /** @var AccessIdentity $identity */
        $identity = Yii::$app->user->getIdentity();
        $entry = new OperationLog();
        $entry->tenant_id = $this->tenantId();
        $entry->account_type = 'tenant';
        $entry->account_id = $identity->accountId;
        $entry->route = Yii::$app->requestedRoute;
        $encoded = is_array($payload)
            ? json_encode($payload, JSON_UNESCAPED_UNICODE)
            : $payload;
        $entry->payload = mb_substr((string)$encoded, 0, 2000);
        $entry->ip = (string)(Yii::$app->request->getUserIP() ?? '');
        $entry->save(false);
    }
}
