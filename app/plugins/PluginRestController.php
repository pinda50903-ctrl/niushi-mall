<?php

declare(strict_types=1);

namespace app\plugins;

use app\common\components\ApiException;
use app\common\components\RestController;
use app\common\plugins\PluginManager;
use Yii;

/**
 * 插件内 API 控制器基类：自动校验租户开通 + 可选身份解析
 *
 * 用户端接口继承本类：游客可访问，登录态自动解析（currentUserId() 辅助校验）。
 */
abstract class PluginRestController extends RestController
{
    /** @var PluginBase */
    public $module;

    abstract protected function pluginCode(): string;

    public function beforeAction($action): bool
    {
        // 先解析身份（允许游客），供 currentUserId / 租户上下文回退使用
        try {
            $identity = \app\common\components\BearerToken::resolveIdentity(Yii::$app->request);
            if ($identity !== null) {
                Yii::$app->user->setIdentity($identity);
            }
        } catch (\yii\web\UnauthorizedHttpException $e) {
            throw new ApiException($e->getMessage(), ApiException::UNAUTHORIZED);
        }
        if (!parent::beforeAction($action)) {
            return false;
        }
        $tenantId = (int)\app\common\services\TenantContext::id();
        if (!PluginManager::tenantHas($this->pluginCode(), $tenantId)) {
            throw new ApiException('商城未开通该插件', ApiException::FORBIDDEN);
        }
        return true;
    }

    protected function tenantId(): int
    {
        return (int)\app\common\services\TenantContext::id();
    }

    /** 当前登录用户 id（未登录抛 401） */
    protected function currentUserId(): int
    {
        $identity = Yii::$app->user->getIdentity();
        if (!$identity instanceof \app\common\components\AccessIdentity
            || !$identity->isUser()
            || (int)$identity->accountId === 0) {
            throw new ApiException('请先登录', ApiException::UNAUTHORIZED);
        }
        return (int)$identity->accountId;
    }
}
