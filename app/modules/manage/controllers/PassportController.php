<?php

declare(strict_types=1);

namespace app\modules\manage\controllers;

use app\common\components\AccessIdentity;
use app\common\components\ApiException;
use app\common\components\BearerToken;
use app\common\components\RestController;
use app\common\components\TokenService;
use app\common\model\Tenant;
use app\common\model\TenantAdmin;
use Yii;

/**
 * 商城管理端登录
 */
final class PassportController extends RestController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['auth'] = [
            'class' => BearerToken::class,
            'only' => ['me', 'password'],
            'types' => [AccessIdentity::TYPE_TENANT],
        ];
        return $behaviors;
    }

    public function actionLogin(): \yii\web\Response
    {
        $username = $this->strInput('username');
        $password = (string)$this->input('password', '');
        if ($username === '' || $password === '') {
            $this->fail('请输入账号和密码');
        }
        /** @var TenantAdmin|null $admin */
        $admin = TenantAdmin::find()
            ->where(['username' => $username, 'status' => 1])
            ->one();
        if (!$admin || !Yii::$app->getSecurity()->validatePassword($password, (string)$admin->password_hash)) {
            $this->fail('账号或密码不正确');
        }
        /** @var Tenant|null $tenant */
        $tenant = Tenant::find()->where(['id' => $admin->tenant_id])->one();
        if (!$tenant || (int)$tenant->status !== 1) {
            $this->fail('商城已停用，请联系平台');
        }

        $admin->last_login_at = time();
        $admin->save(false);
        $token = TokenService::issue(AccessIdentity::TYPE_TENANT, (int)$admin->id, (int)$admin->tenant_id);
        return $this->ok([
            'token' => $token,
            'profile' => $this->profileOf($admin, $tenant),
        ]);
    }

    public function actionMe(): \yii\web\Response
    {
        /** @var AccessIdentity $identity */
        $identity = Yii::$app->user->getIdentity();
        /** @var TenantAdmin $admin */
        $admin = $identity->record;
        /** @var Tenant|null $tenant */
        $tenant = Tenant::find()->where(['id' => $admin->tenant_id])->one();
        $role = $admin->role_id ? \app\common\model\TenantRole::find()->where(['id' => $admin->role_id])->one() : null;
        $profile = $this->profileOf($admin, $tenant);
        $profile['permissions'] = $role && (int)$role->is_super === 1
            ? ['*']
            : (json_decode((string)($role->permissions ?? ''), true) ?: []);
        return $this->ok(['profile' => $profile]);
    }

    public function actionPassword(): \yii\web\Response
    {
        /** @var AccessIdentity $identity */
        $identity = Yii::$app->user->getIdentity();
        /** @var TenantAdmin $admin */
        $admin = $identity->record;
        $old = (string)$this->input('oldPassword', '');
        $new = (string)$this->input('newPassword', '');
        if (!Yii::$app->getSecurity()->validatePassword($old, (string)$admin->password_hash)) {
            $this->fail('原密码不正确');
        }
        if (strlen($new) < 6) {
            $this->fail('新密码至少 6 位');
        }
        $admin->password_hash = Yii::$app->getSecurity()->generatePasswordHash($new);
        $admin->save(false);
        return $this->ok(null, '密码已修改');
    }

    private function profileOf(TenantAdmin $admin, ?Tenant $tenant): array
    {
        return [
            'id' => (int)$admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'type' => AccessIdentity::TYPE_TENANT,
            'tenantId' => (int)$admin->tenant_id,
            'tenantName' => $tenant?->name,
            'tenantLogo' => $tenant?->logo,
            'pluginCodes' => \app\common\plugins\PluginManager::tenantHasAll((int)$admin->tenant_id),
        ];
    }
}
