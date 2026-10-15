<?php

declare(strict_types=1);

namespace app\modules\platform\controllers;

use app\common\components\AccessIdentity;
use app\common\components\ApiException;
use app\common\components\BearerToken;
use app\common\components\RestController;
use app\common\model\PlatformAdmin;
use Yii;

/**
 * 平台总部账号管理
 */
final class AccountController extends RestController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['auth'] = [
            'class' => BearerToken::class,
            'types' => [AccessIdentity::TYPE_PLATFORM],
        ];
        return $behaviors;
    }

    public function actionIndex(): \yii\web\Response
    {
        $query = PlatformAdmin::find()->orderBy('id DESC');
        if (($kw = $this->strInput('keyword')) !== '') {
            $query->andWhere(['or', ['like', 'username', $kw], ['like', 'nickname', $kw]]);
        }
        return $this->okList($query);
    }

    public function actionCreate(): \yii\web\Response
    {
        $username = $this->strInput('username');
        $password = (string)$this->input('password', '');
        if ($username === '' || strlen($password) < 6) {
            $this->fail('账号必填，密码至少 6 位');
        }
        if (PlatformAdmin::find()->where(['username' => $username])->exists()) {
            $this->fail('账号已存在');
        }
        $admin = new PlatformAdmin();
        $admin->username = $username;
        $admin->password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
        $admin->nickname = $this->strInput('nickname', $username);
        $admin->role_id = $this->intInput('roleId');
        $admin->status = 1;
        $admin->save(false);
        return $this->ok(['account' => $admin], '已创建');
    }

    public function actionUpdate(): \yii\web\Response
    {
        $admin = $this->find();
        if ($this->input('nickname') !== null) {
            $admin->nickname = $this->strInput('nickname');
        }
        if ($this->input('roleId') !== null) {
            $admin->role_id = $this->intInput('roleId');
        }
        if ($this->input('status') !== null) {
            $admin->status = (int)$this->input('status') === 1 ? 1 : 0;
        }
        $admin->save(false);
        return $this->ok(['account' => $admin], '已保存');
    }

    public function actionResetPassword(): \yii\web\Response
    {
        $admin = $this->find();
        $password = (string)$this->input('password', '');
        if (strlen($password) < 6) {
            $this->fail('密码至少 6 位');
        }
        $admin->password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
        $admin->save(false);
        return $this->ok(null, '密码已重置');
    }

    public function actionDelete(): \yii\web\Response
    {
        /** @var AccessIdentity $identity */
        $identity = Yii::$app->user->getIdentity();
        $admin = $this->find();
        if ((int)$admin->id === $identity->accountId) {
            $this->fail('不能删除当前登录账号');
        }
        $admin->delete();
        return $this->ok(null, '已删除');
    }

    private function find(): PlatformAdmin
    {
        /** @var PlatformAdmin|null $admin */
        $admin = PlatformAdmin::find()->where(['id' => $this->intInput('id')])->one();
        if (!$admin) {
            throw new ApiException('账号不存在', ApiException::NOT_FOUND);
        }
        return $admin;
    }
}
