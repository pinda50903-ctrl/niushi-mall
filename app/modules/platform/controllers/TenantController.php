<?php

declare(strict_types=1);

namespace app\modules\platform\controllers;

use app\common\components\AccessIdentity;
use app\common\components\ApiException;
use app\common\components\BearerToken;
use app\common\components\RestController;
use app\common\model\Tenant;
use app\common\model\TenantAdmin;
use app\common\model\TenantRole;
use Yii;

/**
 * 租户（商城）管理
 */
final class TenantController extends RestController
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
        $query = Tenant::find();
        $keyword = $this->strInput('keyword');
        if ($keyword !== '') {
            $query->andWhere(['or', ['like', 'name', $keyword], ['like', 'slug', $keyword]]);
        }
        $status = $this->input('status');
        if ($status !== null && $status !== '') {
            $query->andWhere(['status' => (int)$status]);
        }
        $query->orderBy('id DESC');
        return $this->okList($query);
    }

    public function actionView(): \yii\web\Response
    {
        return $this->ok(['tenant' => $this->find()]);
    }

    /** 开通新商城：建租户 + 超管角色 + 初始管理员 */
    public function actionCreate(): \yii\web\Response
    {
        $name = $this->strInput('name');
        $slug = $this->strInput('slug');
        $username = $this->strInput('username');
        $password = (string)$this->input('password', '');
        if ($name === '' || $slug === '' || $username === '' || strlen($password) < 6) {
            $this->fail('请完整填写名称、标识、管理员账号（密码至少 6 位）');
        }
        if (!preg_match('/^[a-z][a-z0-9_-]{1,31}$/', $slug)) {
            $this->fail('标识仅限小写字母开头的字母数字');
        }
        if (Tenant::find()->where(['slug' => $slug])->exists()) {
            $this->fail('标识已被占用');
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            $tenant = new Tenant();
            $tenant->name = $name;
            $tenant->slug = $slug;
            $tenant->phone = $this->strInput('phone');
            $tenant->status = 1;
            $expire = (string)$this->input('expireAt', '');
            $tenant->expire_at = $expire !== '' ? (int)strtotime($expire) : 0;
            $tenant->remark = $this->strInput('remark');
            $tenant->save(false);

            $role = new TenantRole();
            $role->tenant_id = (int)$tenant->id;
            $role->name = '超级管理员';
            $role->is_super = 1;
            $role->save(false);

            $admin = new TenantAdmin();
            $admin->tenant_id = (int)$tenant->id;
            $admin->username = $username;
            $admin->password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
            $admin->nickname = '店主';
            $admin->role_id = (int)$role->id;
            $admin->save(false);

            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
        return $this->ok(['tenant' => $tenant], '商城已开通');
    }

    public function actionUpdate(): \yii\web\Response
    {
        $tenant = $this->find();
        foreach (['name', 'phone', 'remark'] as $field) {
            if ($this->input($field) !== null) {
                $tenant->$field = $this->strInput($field);
            }
        }
        if ($this->input('expireAt') !== null) {
            $raw = (string)$this->input('expireAt');
            $tenant->expire_at = $raw !== '' ? (int)strtotime($raw) : 0;
        }
        if ($this->input('status') !== null) {
            $tenant->status = (int)$this->input('status') === 1 ? 1 : 0;
        }
        $tenant->save(false);
        return $this->ok(['tenant' => $tenant], '已保存');
    }

    /** 重置租户管理员密码 */
    public function actionResetPassword(): \yii\web\Response
    {
        $adminId = $this->intInput('adminId');
        /** @var TenantAdmin|null $admin */
        $admin = TenantAdmin::find()->where(['id' => $adminId])->one();
        if (!$admin) {
            $this->fail('管理员不存在', ApiException::NOT_FOUND);
        }
        $password = (string)$this->input('password', '');
        if (strlen($password) < 6) {
            $this->fail('密码至少 6 位');
        }
        $admin->password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
        $admin->save(false);
        return $this->ok(null, '密码已重置');
    }

    private function find(): Tenant
    {
        /** @var Tenant|null $tenant */
        $tenant = Tenant::find()->where(['id' => $this->intInput('id')])->one();
        if (!$tenant) {
            throw new ApiException('商城不存在', ApiException::NOT_FOUND);
        }
        return $tenant;
    }
}
