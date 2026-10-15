<?php

declare(strict_types=1);

namespace app\console\controllers;

use Yii;

/**
 * 迁移控制器：为 migrationNamespaces 提供通配符支持
 *
 * 支持 'app\plugins\*\migrations' 形式，自动展开为每个插件目录的具体命名空间。
 * 原生 MigrateController 将命名空间按字面路径扫描，星号目录不存在会被整体跳过。
 */
final class MigrateController extends \yii\console\controllers\MigrateController
{
    protected function getNewMigrations(): array
    {
        $this->migrationNamespaces = $this->expandNamespaces($this->migrationNamespaces);
        return parent::getNewMigrations();
    }

    /**
     * 展开通配命名空间：app\plugins\*\migrations → 每个含 migrations 目录的插件
     * @param string[] $namespaces
     * @return string[]
     */
    private function expandNamespaces(array $namespaces): array
    {
        $expanded = [];
        foreach ($namespaces as $namespace) {
            if (strpos($namespace, '*') === false) {
                $expanded[] = $namespace;
                continue;
            }
            [$baseNamespace] = explode('\\*', $namespace);
            $basePath = Yii::getAlias('@' . str_replace('\\', '/', $baseNamespace));
            if ($basePath === false || !is_dir($basePath)) {
                continue;
            }
            $dirs = glob(rtrim($basePath, '/\\') . '/*', GLOB_ONLYDIR) ?: [];
            foreach ($dirs as $dir) {
                if (is_dir($dir . '/migrations')) {
                    $expanded[] = $baseNamespace . '\\' . basename($dir) . '\\migrations';
                }
            }
        }
        return $expanded;
    }
}
