<?php

declare(strict_types=1);

namespace app\plugins;

use app\common\components\ApiException;
use app\common\plugins\PluginManager;
use yii\base\Module as BaseModule;

/**
 * 插件基类：所有插件入口必须继承本类
 *
 * 子类位于 app\plugins\<code>\Plugin.php，实现 manifest() 描述自身。
 * 启用的插件由 PluginManager 注册为应用模块，路由形如 /<code>/<controller>/<action>。
 */
abstract class PluginBase extends BaseModule
{
    /** 插件代码，必须与目录名一致 */
    abstract public static function code(): string;

    /** 插件名称 */
    abstract public static function name(): string;

    /** 分类：marketing 交易营销 / member 会员增长 / store 门店渠道 / tool 辅助工具 / channel 多端渠道 */
    public static function category(): string
    {
        return 'tool';
    }

    /** 一句话描述 */
    public static function description(): string
    {
        return '';
    }

    /** 版本 */
    public static function version(): string
    {
        return '1.0.0';
    }

    /** 汇总 manifest（PluginManager 读取） */
    final public static function manifest(): array
    {
        return [
            'code' => static::code(),
            'name' => static::name(),
            'category' => static::category(),
            'description' => static::description(),
            'version' => static::version(),
        ];
    }

    /**
     * 请求级引导钩子：注册全局事件监听等（每个请求最多调用一次）
     */
    public static function bootstrap(): void
    {
    }

    /* ---------- 运行时辅助 ---------- */

    /** 插件命名空间下的控制器基类 */
    public $controllerNamespace = '';

    public function init(): void
    {
        parent::init();
        if ($this->controllerNamespace === '') {
            $this->controllerNamespace = 'app\\plugins\\' . static::code() . '\\controllers';
        }
    }

    /**
     * 当前租户是否已开通本插件（未开通直接拒绝）
     */
    protected function tenantId(): int
    {
        return (int)\app\common\services\TenantContext::id();
    }

    /**
     * 校验当前请求的租户已开通本插件
     */
    protected function requireTenantGrant(): void
    {
        $tenantId = $this->tenantId();
        if (!PluginManager::tenantHas(static::code(), $tenantId)) {
            throw new ApiException('商城未开通「' . static::name() . '」插件', ApiException::FORBIDDEN);
        }
    }
}
