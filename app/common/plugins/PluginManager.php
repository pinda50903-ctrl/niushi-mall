<?php

declare(strict_types=1);

namespace app\common\plugins;

use Yii;
use yii\base\Event;
use yii\caching\Dependency;

/**
 * 插件管理器：扫描 app/plugins/*，将启用的插件注册为应用模块
 *
 * 每个插件目录结构：
 *   plugins/<code>/Plugin.php   实现 app\plugins\<code>\Plugin
 */
class PluginManager
{
    public static function handleBeforeRequest(Event $event): void
    {
        self::registerEnabled();
    }

    /**
     * 已启用插件代码列表（应用级，租户级开关由运行时校验）
     * @return string[]
     */
    public static function enabledCodes(): array
    {
        static $codes = null;
        if ($codes !== null) {
            return $codes;
        }
        $codes = Yii::$app->getCache()->getOrSet('plugin:enabled', static function (): array {
            $rows = Yii::$app->db->createCommand(
                'SELECT code FROM {{%plugin}} WHERE status = 1'
            )->queryColumn();
            return array_map('strval', $rows);
        }, 60);
        return $codes;
    }

    /**
     * 租户是否开通某插件
     */
    public static function tenantHas(string $pluginCode, int $tenantId): bool
    {
        if (!in_array($pluginCode, self::enabledCodes(), true)) {
            return false;
        }
        return (bool)Yii::$app->db->createCommand(
            'SELECT COUNT(*) FROM {{%tenant_plugin}} WHERE tenant_id = :t AND code = :c AND status = 1 AND (expire_at = 0 OR expire_at > :now)'
        )->bindValues([':t' => $tenantId, ':c' => $pluginCode, ':now' => time()])->queryScalar();
    }

    /**
     * 租户已开通的插件代码列表
     * @return string[]
     */
    public static function tenantHasAll(int $tenantId): array
    {
        $enabled = array_flip(self::enabledCodes());
        $owned = Yii::$app->db->createCommand(
            'SELECT code FROM {{%tenant_plugin}} WHERE tenant_id = :t AND status = 1 AND (expire_at = 0 OR expire_at > :now)'
        )->bindValues([':t' => $tenantId, ':now' => time()])->queryColumn();
        return array_values(array_intersect(array_map('strval', $owned), array_keys($enabled)));
    }

    /** @var bool 本请求是否已执行过插件引导 */
    private static bool $booted = false;

    /**
     * 注册所有已启用的插件模块
     */
    public static function registerEnabled(): void
    {
        if (Yii::$app instanceof \yii\console\Application) {
            return; // 控制台通过迁移命名空间通配加载插件迁移
        }
        $enabled = self::enabledCodes();
        foreach (self::available() as $code => $class) {
            if (in_array($code, $enabled, true)) {
                Yii::$app->setModule($code, ['class' => $class]);
            }
        }
        if (!self::$booted) {
            self::$booted = true;
            foreach ($enabled as $code) {
                $class = self::available()[$code] ?? null;
                if ($class !== null && method_exists($class, 'bootstrap')) {
                    $class::bootstrap();
                }
            }
        }
    }

    /**
     * @return array<string, string> code => 插件模块类
     */
    public static function available(): array
    {
        static $map = null;
        if ($map !== null) {
            return $map;
        }
        $map = [];
        $dir = Yii::getAlias('@plugins');
        if (!is_dir($dir)) {
            return $map;
        }
        foreach (glob($dir . '/*/Plugin.php') ?: [] as $file) {
            $code = basename(dirname($file));
            $class = "app\\plugins\\{$code}\\Plugin";
            if (!class_exists($class, true)) {
                continue;
            }
            $manifest = $class::manifest();
            if (($manifest['code'] ?? '') !== $code) {
                continue;
            }
            $map[$code] = $class;
        }
        ksort($map);
        return $map;
    }

    /**
     * 插件元信息汇总
     */
    public static function catalog(): array
    {
        $list = [];
        foreach (self::available() as $code => $class) {
            $list[] = $class::manifest();
        }
        return $list;
    }
}
