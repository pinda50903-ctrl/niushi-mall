<?php

declare(strict_types=1);

namespace app\plugins\points_mall\controllers;

use app\plugins\PluginRestController;

/**
 * 积分商城管理端接口：插件配置读写
 */
final class AdminController extends PluginRestController
{
    protected function pluginCode(): string
    {
        return 'points_mall';
    }

    /** 读取插件配置 */
    public function actionConfig(): \yii\web\Response
    {
        return $this->ok([
            'config' => \app\common\services\PluginSettingService::get($this->pluginCode(), $this->tenantId()),
        ]);
    }

    /** 保存插件配置 */
    public function actionSaveConfig(): \yii\web\Response
    {
        $value = (array)($this->input('config') ?? []);
        \app\common\services\PluginSettingService::save($this->pluginCode(), $value, $this->tenantId());
        return $this->ok(null, '积分商城配置已保存');
    }
}