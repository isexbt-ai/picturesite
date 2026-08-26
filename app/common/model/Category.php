<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 分类模型
 */
class Category extends BaseModel
{
    protected $name = 'categories';

    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;

    /**
     * 分类下的内容
     */
    public function albums(): \think\model\relation\HasMany
    {
        return $this->hasMany(Album::class, 'category_id');
    }
}
