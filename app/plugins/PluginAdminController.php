<?php

declare(strict_types=1);

namespace app\plugins;

use app\common\components\ApiException;
use Yii;

/**
 * 插件管理端控制器基类：强制商城管理员身份
 */
abstract class PluginAdminController extends PluginRestController
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $identity = Yii::$app->user->getIdentity();
        if (!$identity instanceof \app\common\components\AccessIdentity || !$identity->isTenantAdmin()) {
            throw new ApiException('请使用商城管理员账号登录', ApiException::UNAUTHORIZED);
        }
        return true;
    }
}
