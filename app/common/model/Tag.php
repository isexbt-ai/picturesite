<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 标签模型
 */
class Tag extends BaseModel
{
    protected $name = 'tags';

    /**
     * 标签下的内容
     */
    public function albums(): \think\model\relation\BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'album_tags', 'tag_id', 'album_id');
    }
}
