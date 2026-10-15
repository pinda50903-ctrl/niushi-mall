<?php

declare(strict_types=1);

namespace app\modules\manage\controllers;

use app\common\services\SettingService;
use app\common\services\UploadService;
use app\common\model\Attachment;

/**
 * 商城设置 + 素材库 + 仪表盘
 */
final class SettingController extends TenantAwareController
{
    protected string $permission = 'mall:setting';

    public function actionGet(): \yii\web\Response
    {
        $code = $this->strInput('code');
        $tenantId = $this->tenantId();
        if ($code === 'all') {
            $all = [];
            foreach (array_keys(SettingService::DEFAULTS) as $key) {
                $all[$key] = SettingService::get($key, $tenantId);
            }
            return $this->ok(['settings' => $all]);
        }
        if (!isset(SettingService::DEFAULTS[$code])) {
            $this->fail('未知的设置项');
        }
        return $this->ok(['settings' => SettingService::get($code, $tenantId)]);
    }

    public function actionSave(): \yii\web\Response
    {
        $code = $this->strInput('code');
        $value = (array)($this->input('value') ?? []);
        if (!isset(SettingService::DEFAULTS[$code])) {
            $this->fail('未知的设置项');
        }
        $saved = SettingService::patch($code, $value, $this->tenantId());
        $this->logOperation(['op' => 'save_setting', 'code' => $code]);
        return $this->ok(['settings' => $saved], '设置已保存');
    }

    /* ---------- 素材库 ---------- */

    public function actionUpload(): \yii\web\Response
    {
        $file = \yii\web\UploadedFile::getInstanceByName('file');
        if (!$file) {
            $this->fail('请选择文件');
        }
        $attachment = UploadService::image($file, $this->tenantId());
        return $this->ok(['attachment' => $attachment]);
    }

    public function actionAttachments(): \yii\web\Response
    {
        $query = Attachment::find()
            ->where(['tenant_id' => $this->tenantId()])
            ->orderBy('id DESC');
        return $this->okList($query);
    }

    public function actionDeleteAttachment(): \yii\web\Response
    {
        $id = $this->intInput('id');
        /** @var Attachment|null $row */
        $row = Attachment::find()
            ->where(['id' => $id, 'tenant_id' => $this->tenantId()])
            ->one();
        if ($row) {
            $abs = \Yii::getAlias('@app/web/' . $row->path);
            if (is_file($abs)) {
                @unlink($abs);
            }
            $row->delete();
        }
        return $this->ok(null, '已删除');
    }
}
