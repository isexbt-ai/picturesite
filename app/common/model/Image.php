<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 图片模型
 */
class Image extends BaseModel
{
    protected $name = 'images';

    /**
     * 所属内容
     */
    public function album(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Album::class, 'album_id');
    }
}
