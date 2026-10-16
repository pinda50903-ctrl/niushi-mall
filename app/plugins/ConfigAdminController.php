<?php

declare(strict_types=1);

namespace app\plugins;

/**
 * 配置型插件管理端控制器基类
 *
 * 对外暴露统一契约，管理端 PluginGeneric 无需为每个插件单独开发：
 *   GET  /<code>/admin/config        读取配置
 *   POST /<code>/admin/save-config   保存配置
 *
 * 子类通过 serviceClass() 指向各自的 ConfigurableService 实现。
 */
abstract class ConfigAdminController extends PluginAdminController
{
    /** @var class-string<ConfigurableService> */
    abstract protected function serviceClass(): string;

    /** 读取插件配置 */
    public function actionConfig(): \yii\web\Response
    {
        $service = $this->serviceClass();
        return $this->ok(['config' => $service::config($this->tenantId())]);
    }

    /** 保存插件配置 */
    public function actionSaveConfig(): \yii\web\Response
    {
        $service = $this->serviceClass();
        $input = (array)($this->input('config') ?? []);
        $service::save($this->tenantId(), $input);
        return $this->ok(['config' => $service::config($this->tenantId())], '配置已保存');
    }
}
