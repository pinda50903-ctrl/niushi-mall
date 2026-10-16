<?php

declare(strict_types=1);

namespace app\plugins;

/**
 * 配置型插件服务基类
 *
 * 多数插件的业务形态是「一组可配置参数」，差异集中在：
 * - defaults()：默认配置项（值类型决定管理端表单控件）
 * - cast()：提交值的类型收敛与边界裁剪
 *
 * 子类只需覆盖 code() 与 defaults()，即可获得统一的 config()/save()。
 */
abstract class ConfigurableService
{
    /** 插件代码（与目录名一致） */
    abstract public static function code(): string;

    /**
     * 默认配置
     *
     * 键名采用 snake_case，值类型约定：
     * bool → 开关 / int|float → 数字输入 / string → 文本 / array → JSON
     *
     * @return array<string, mixed>
     */
    abstract public static function defaults(): array;

    /**
     * 收敛提交值：按 defaults 的类型转换，未提供的键回落默认值
     *
     * @param  array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function cast(array $input): array
    {
        $out = [];
        foreach (static::defaults() as $key => $default) {
            $out[$key] = array_key_exists($key, $input)
                ? self::castOne($input[$key], $default)
                : $default;
        }
        return $out;
    }

    /** 单值类型收敛 */
    private static function castOne(mixed $value, mixed $default): mixed
    {
        if (is_bool($default)) {
            return (bool)$value;
        }
        if (is_int($default)) {
            return (int)$value;
        }
        if (is_float($default)) {
            return round((float)$value, 2);
        }
        if (is_array($default)) {
            return is_array($value) ? $value : $default;
        }
        return is_scalar($value) ? trim((string)$value) : $default;
    }

    /** 读取配置（默认值与已保存值合并） */
    public static function config(int $tenantId): array
    {
        $saved = \app\common\services\PluginSettingService::get(static::code(), $tenantId);
        return array_merge(static::defaults(), is_array($saved) ? $saved : []);
    }

    /** 保存配置 */
    public static function save(int $tenantId, array $input): array
    {
        $value = static::cast($input);
        \app\common\services\PluginSettingService::save(static::code(), $value, $tenantId);
        return $value;
    }
}
