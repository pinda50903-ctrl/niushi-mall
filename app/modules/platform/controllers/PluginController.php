<?php

declare(strict_types=1);

namespace app\modules\platform\controllers;

use app\common\components\AccessIdentity;
use app\common\components\ApiException;
use app\common\components\BearerToken;
use app\common\components\RestController;
use app\common\model\Plugin;
use app\common\model\Tenant;
use app\common\model\TenantPlugin;
use app\common\plugins\PluginManager;
use Yii;

/**
 * 插件市场：本地插件扫描 + 上架 + 租户授权
 */
final class PluginController extends RestController
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

    /** 插件市场（目录扫描 + 注册表状态合并） */
    public function actionMarket(): \yii\web\Response
    {
        $registered = [];
        foreach (Plugin::find()->each() as $row) {
            /** @var Plugin $row */
            $registered[$row->code] = $row;
        }
        $list = [];
        foreach (PluginManager::catalog() as $manifest) {
            $code = $manifest['code'];
            /** @var Plugin|null $row */
            $row = $registered[$code] ?? null;
            $list[] = [
                'code' => $code,
                'name' => $manifest['name'] ?? $code,
                'category' => $manifest['category'] ?? '',
                'description' => $manifest['description'] ?? '',
                'version' => $manifest['version'] ?? '1.0.0',
                'status' => $row ? (int)$row->status : 0,
                'is_registered' => $row !== null,
            ];
        }
        // 本地扫描不到但注册表里有的（已下线插件）
        foreach ($registered as $code => $row) {
            if (!isset(array_column($list, null, 'code')[$code])) {
                $list[] = [
                    'code' => $row->code,
                    'name' => $row->name,
                    'category' => $row->category,
                    'description' => $row->description,
                    'version' => '-',
                    'status' => (int)$row->status,
                    'is_registered' => true,
                ];
            }
        }
        return $this->ok(['list' => array_values($list)]);
    }

    /** 上架/下架插件 */
    public function actionToggle(): \yii\web\Response
    {
        $code = $this->strInput('code');
        $manifests = array_column(PluginManager::catalog(), null, 'code');
        $manifest = $manifests[$code] ?? null;
        if (!$manifest) {
            throw new ApiException('插件不存在或未安装', ApiException::NOT_FOUND);
        }
        $status = (int)$this->input('status', 1) === 1 ? 1 : 0;
        /** @var Plugin|null $row */
        $row = Plugin::find()->where(['code' => $code])->one();
        if (!$row) {
            $row = new Plugin();
            $row->code = $code;
            $row->name = $manifest['name'] ?? $code;
            $row->category = $manifest['category'] ?? '';
            $row->description = $manifest['description'] ?? '';
            $row->created_at = time();
        }
        $row->status = $status;
        $row->updated_at = time();
        $row->save(false);
        Yii::$app->getCache()->delete('plugin:enabled');
        return $this->ok(null, $status === 1 ? '插件已上架' : '插件已下架');
    }

    /** 租户授权列表 */
    public function actionTenantGrant(): \yii\web\Response
    {
        $code = $this->strInput('code');
        if ($code === '') {
            $this->fail('缺少插件代码');
        }
        $rows = (new \yii\db\Query())
            ->select(['tp.*', 't.name AS tenant_name'])
            ->from(['tp' => '{{%tenant_plugin}}'])
            ->innerJoin(['t' => '{{%tenant}}'], 't.id = tp.tenant_id')
            ->where(['tp.code' => $code])
            ->orderBy('tp.id DESC')
            ->all();
        return $this->ok(['list' => $rows]);
    }

    /** 给租户开通/续期插件 */
    public function actionGrant(): \yii\web\Response
    {
        $code = $this->strInput('code');
        $tenantId = $this->intInput('tenantId');
        if (!Plugin::find()->where(['code' => $code, 'status' => 1])->exists()) {
            $this->fail('插件未上架');
        }
        /** @var Tenant|null $tenant */
        $tenant = Tenant::find()->where(['id' => $tenantId])->one();
        if (!$tenant) {
            throw new ApiException('商城不存在', ApiException::NOT_FOUND);
        }
        /** @var TenantPlugin|null $row */
        $row = TenantPlugin::find()->where(['tenant_id' => $tenantId, 'code' => $code])->one();
        if (!$row) {
            $row = new TenantPlugin();
            $row->tenant_id = $tenantId;
            $row->code = $code;
            $row->created_at = time();
        }
        $row->status = 1;
        $expire = (string)$this->input('expireAt', '');
        $row->expire_at = $expire !== '' ? (int)strtotime($expire) : 0;
        $row->updated_at = time();
        $row->save(false);
        return $this->ok(null, '已授权');
    }

    /** 撤销租户插件 */
    public function actionRevoke(): \yii\web\Response
    {
        TenantPlugin::deleteAll([
            'code' => $this->strInput('code'),
            'tenant_id' => $this->intInput('tenantId'),
        ]);
        return $this->ok(null, '已撤销');
    }
}
