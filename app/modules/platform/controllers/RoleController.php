<?php

declare(strict_types=1);

namespace app\modules\platform\controllers;

use app\common\components\AccessIdentity;
use app\common\components\ApiException;
use app\common\components\BearerToken;
use app\common\components\RestController;
use app\common\model\PlatformRole;

/**
 * 平台角色与权限点
 */
final class RoleController extends RestController
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

    /** 权限点字典 */
    public const PERMISSIONS = [
        ['group' => '总览', 'items' => [
            'platform:overview' => '数据总览',
        ]],
        ['group' => '商城', 'items' => [
            'platform:tenant' => '商城管理',
        ]],
        ['group' => '账号', 'items' => [
            'platform:account' => '账号管理',
            'platform:role' => '角色权限',
        ]],
        ['group' => '插件', 'items' => [
            'platform:plugin' => '插件管理',
        ]],
    ];

    public function actionPermissions(): \yii\web\Response
    {
        return $this->ok(self::PERMISSIONS);
    }

    public function actionIndex(): \yii\web\Response
    {
        $query = PlatformRole::find()->orderBy('id ASC');
        return $this->okList($query);
    }

    public function actionSave(): \yii\web\Response
    {
        $id = $this->intInput('id');
        if ($id > 0) {
            /** @var PlatformRole|null $role */
            $role = PlatformRole::find()->where(['id' => $id])->one();
            if (!$role) {
                throw new ApiException('角色不存在', ApiException::NOT_FOUND);
            }
            if ((int)$role->is_super === 1) {
                $this->fail('超管角色不可编辑');
            }
        } else {
            $role = new PlatformRole();
            $role->is_super = 0;
        }
        $name = $this->strInput('name');
        if ($name === '') {
            $this->fail('请填写角色名称');
        }
        $role->name = $name;
        $permissions = (array)($this->input('permissions') ?? []);
        $allowed = [];
        foreach (self::PERMISSIONS as $group) {
            foreach (array_keys($group['items']) as $perm) {
                $allowed[] = $perm;
            }
        }
        $role->permissions = json_encode(array_values(array_intersect($permissions, $allowed)), JSON_UNESCAPED_UNICODE);
        $role->remark = $this->strInput('remark');
        $role->save(false);
        return $this->ok(['role' => $role], '已保存');
    }

    public function actionDelete(): \yii\web\Response
    {
        /** @var PlatformRole|null $role */
        $role = PlatformRole::find()->where(['id' => $this->intInput('id')])->one();
        if (!$role) {
            throw new ApiException('角色不存在', ApiException::NOT_FOUND);
        }
        if ((int)$role->is_super === 1) {
            $this->fail('超管角色不可删除');
        }
        if (PlatformAdmin::find()->where(['role_id' => $role->id])->exists()) {
            $this->fail('有账号正在使用该角色');
        }
        $role->delete();
        return $this->ok(null, '已删除');
    }
}
