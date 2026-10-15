<?php

declare(strict_types=1);

namespace app\modules\api\controllers;

use app\common\components\AccessIdentity;
use app\common\components\ApiException;
use app\common\components\BearerToken;
use app\common\components\RestController;
use app\common\model\User;
use app\common\services\TenantContext;
use Yii;

/**
 * 用户端控制器基类
 *
 * - 租户定位：X-Mall-Id 头或 tenantId 参数
 * - $loginRequired = true 时强制登录，false 时通过 currentUser() 拿可选身份
 */
abstract class UserAwareController extends RestController
{
    protected bool $loginRequired = false;

    /**
     * 用户端不做强制 auth filter：有 token 就解析身份，无 token 允许匿名；
     * 需登录的接口由 userId()/currentUser() 决定。
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        try {
            $identity = BearerToken::resolveIdentity(Yii::$app->request);
            if ($identity !== null) {
                if ($identity->type !== AccessIdentity::TYPE_USER) {
                    throw new ApiException('请使用用户身份登录', ApiException::UNAUTHORIZED);
                }
                Yii::$app->user->setIdentity($identity);
            }
        } catch (\yii\web\UnauthorizedHttpException $e) {
            if ($this->loginRequired) {
                throw new ApiException($e->getMessage(), ApiException::UNAUTHORIZED);
            }
            // 匿名接口忽略无效 token
        }
        if ($this->loginRequired && $this->currentUser() === null) {
            throw new ApiException('请先登录', ApiException::UNAUTHORIZED);
        }
        return true;
    }

    /** 当前登录用户（未登录返回 null） */
    protected function currentUser(): ?User
    {
        $identity = Yii::$app->user->getIdentity();
        return $identity instanceof AccessIdentity ? $identity->record : null;
    }

    /** 当前用户 id（未登录/游客抛 40100） */
    protected function userId(): int
    {
        $user = $this->currentUser();
        if (!$user || (int)$user->id === 0) {
            throw new ApiException('请先登录', ApiException::UNAUTHORIZED);
        }
        return (int)$user->id;
    }

    protected function tenantId(): int
    {
        return TenantContext::id();
    }
}
