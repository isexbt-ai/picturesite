<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 视频模型（视频内容对应的文件记录）
 */
class Video extends BaseModel
{
    protected $name = 'videos';

    /**
     * 所属内容
     */
    public function album(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Album::class, 'album_id');
    }
}
