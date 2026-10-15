<?php

declare(strict_types=1);

namespace app\modules\manage\controllers;

use app\common\components\ApiException;
use app\common\model\TenantAdmin;
use app\common\model\TenantRole;
use Yii;

/**
 * 子账号与角色（商城内部）
 */
final class AccountController extends TenantAwareController
{
    protected string $permission = 'mall:account';

    public const PERMISSIONS = [
        ['group' => '交易', 'items' => [
            'mall:order' => '订单售后',
            'mall:refund' => '售后处理',
        ]],
        ['group' => '商品', 'items' => [
            'mall:goods' => '商品管理',
            'mall:category' => '分类管理',
            'mall:freight' => '运费模板',
        ]],
        ['group' => '用户', 'items' => [
            'mall:user' => '用户管理',
            'mall:coupon' => '优惠券',
        ]],
        ['group' => '内容', 'items' => [
            'mall:content' => '内容装修',
        ]],
        ['group' => '系统', 'items' => [
            'mall:dashboard' => '仪表盘',
            'mall:setting' => '商城设置',
            'mall:account' => '子账号',
        ]],
    ];

    public function actionPermissions(): \yii\web\Response
    {
        return $this->ok(self::PERMISSIONS);
    }

    /* ---------- 子账号 ---------- */

    public function actionIndex(): \yii\web\Response
    {
        $query = TenantAdmin::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('id ASC');
        return $this->okList($query);
    }

    public function actionSave(): \yii\web\Response
    {
        $id = $this->intInput('id');
        $username = $this->strInput('username');
        if ($username === '') {
            $this->fail('请填写账号');
        }
        /** @var TenantAdmin|null $admin */
        $admin = $id > 0
            ? TenantAdmin::find()->where(['id' => $id, 'tenant_id' => $this->tenantId()])->one()
            : null;
        if ($id > 0 && !$admin) {
            throw new ApiException('账号不存在', ApiException::NOT_FOUND);
        }
        if (!$admin) {
            if (strlen((string)$this->input('password', '')) < 6) {
                $this->fail('密码至少 6 位');
            }
            if (TenantAdmin::find()->where(['tenant_id' => $this->tenantId(), 'username' => $username])->exists()) {
                $this->fail('账号已存在');
            }
            $admin = new TenantAdmin();
            $admin->tenant_id = $this->tenantId();
            $admin->username = $username;
            $admin->password_hash = Yii::$app->getSecurity()->generatePasswordHash((string)$this->input('password'));
        }
        $admin->nickname = $this->strInput('nickname', $username);
        $admin->role_id = $this->intInput('roleId');
        if ($this->input('status') !== null) {
            $admin->status = (int)$this->input('status') === 1 ? 1 : 0;
        }
        $admin->save(false);
        return $this->ok(['account' => $admin], '已保存');
    }

    public function actionResetPassword(): \yii\web\Response
    {
        /** @var TenantAdmin|null $admin */
        $admin = TenantAdmin::find()
            ->where(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()])
            ->one();
        if (!$admin) {
            throw new ApiException('账号不存在', ApiException::NOT_FOUND);
        }
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
        TenantAdmin::deleteAll(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()]);
        return $this->ok(null, '已删除');
    }

    /* ---------- 角色 ---------- */

    public function actionRoles(): \yii\web\Response
    {
        $query = TenantRole::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('id ASC');
        return $this->okList($query);
    }

    public function actionSaveRole(): \yii\web\Response
    {
        $id = $this->intInput('id');
        $name = $this->strInput('name');
        if ($name === '') {
            $this->fail('请填写角色名称');
        }
        /** @var TenantRole|null $role */
        $role = $id > 0
            ? TenantRole::find()->where(['id' => $id, 'tenant_id' => $this->tenantId()])->one()
            : null;
        if ($id > 0 && !$role) {
            throw new ApiException('角色不存在', ApiException::NOT_FOUND);
        }
        if ($role && (int)$role->is_super === 1) {
            $this->fail('超管角色不可编辑');
        }
        if (!$role) {
            $role = new TenantRole();
            $role->tenant_id = $this->tenantId();
            $role->is_super = 0;
        }
        $role->name = $name;
        $allowed = [];
        foreach (self::PERMISSIONS as $group) {
            $allowed = array_merge($allowed, array_keys($group['items']));
        }
        $permissions = array_values(array_intersect((array)($this->input('permissions') ?? []), $allowed));
        $role->permissions = json_encode($permissions, JSON_UNESCAPED_UNICODE);
        $role->save(false);
        return $this->ok(['role' => $role], '已保存');
    }

    public function actionDeleteRole(): \yii\web\Response
    {
        /** @var TenantRole|null $role */
        $role = TenantRole::find()
            ->where(['id' => $this->intInput('id'), 'tenant_id' => $this->tenantId()])
            ->one();
        if (!$role) {
            throw new ApiException('角色不存在', ApiException::NOT_FOUND);
        }
        if ((int)$role->is_super === 1) {
            $this->fail('超管角色不可删除');
        }
        if (TenantAdmin::find()->where(['role_id' => $role->id])->exists()) {
            $this->fail('有账号正在使用该角色');
        }
        $role->delete();
        return $this->ok(null, '已删除');
    }
}
