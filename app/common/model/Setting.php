<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 系统设置模型
 */
class Setting extends BaseModel
{
    protected $name = 'settings';

    /**
     * 读取单个配置值，无则返回默认值
     */
    public static function getValue(string $key, string $default = ''): string
    {
        $row = self::where('key', $key)->find();
        return $row ? (string) $row->value : $default;
    }

    /**
     * 写入配置值（存在则更新）
     */
    public static function setValue(string $key, string $value): void
    {
        $row = self::where('key', $key)->find();
        if ($row) {
            $row->value = $value;
            $row->save();
        } else {
            self::create(['key' => $key, 'value' => $value]);
        }
    }
}
