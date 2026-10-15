<?php

declare(strict_types=1);

namespace app\modules\platform\controllers;

use app\common\components\AccessIdentity;
use app\common\components\ApiException;
use app\common\components\BearerToken;
use app\common\components\RestController;
use app\common\components\TokenService;
use app\common\model\OperationLog;
use app\common\model\PlatformAdmin;
use Yii;

/**
 * 平台总部登录
 */
final class PassportController extends RestController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['auth'] = [
            'class' => BearerToken::class,
            'only' => ['me', 'logout'],
            'types' => [AccessIdentity::TYPE_PLATFORM],
        ];
        return $behaviors;
    }

    /** 账号密码登录 */
    public function actionLogin(): \yii\web\Response
    {
        $username = $this->strInput('username');
        $password = (string)$this->input('password', '');
        if ($username === '' || $password === '') {
            $this->fail('请输入账号和密码');
        }
        /** @var PlatformAdmin|null $admin */
        $admin = PlatformAdmin::find()
            ->where(['username' => $username, 'status' => 1])
            ->one();
        if (!$admin || !Yii::$app->getSecurity()->validatePassword($password, (string)$admin->password_hash)) {
            $this->fail('账号或密码不正确');
        }

        $admin->last_login_at = time();
        $admin->save(false);

        $token = TokenService::issue(AccessIdentity::TYPE_PLATFORM, (int)$admin->id, null);
        $this->log($admin, 'platform/passport/login');

        return $this->ok([
            'token' => $token,
            'profile' => $this->profileOf($admin),
        ]);
    }

    public function actionMe(): \yii\web\Response
    {
        /** @var AccessIdentity $identity */
        $identity = Yii::$app->user->getIdentity();
        /** @var PlatformAdmin $admin */
        $admin = $identity->record;
        $role = $admin->role_id ? \app\common\model\PlatformRole::find()->where(['id' => $admin->role_id])->one() : null;
        $profile = $this->profileOf($admin);
        $profile['permissions'] = $role && (int)$role->is_super === 1
            ? ['*']
            : (json_decode((string)($role->permissions ?? ''), true) ?: []);
        return $this->ok(['profile' => $profile]);
    }

    /** 修改密码 */
    public function actionPassword(): \yii\web\Response
    {
        /** @var AccessIdentity $identity */
        $identity = Yii::$app->user->getIdentity();
        /** @var PlatformAdmin $admin */
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

    private function profileOf(PlatformAdmin $admin): array
    {
        return [
            'id' => (int)$admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'type' => AccessIdentity::TYPE_PLATFORM,
        ];
    }

    private function log(PlatformAdmin $admin, string $route): void
    {
        $entry = new OperationLog();
        $entry->tenant_id = 0;
        $entry->account_type = 'platform';
        $entry->account_id = (int)$admin->id;
        $entry->route = $route;
        $entry->payload = '';
        $entry->ip = (string)(Yii::$app->request->getUserIP() ?? '');
        $entry->save(false);
    }
}
