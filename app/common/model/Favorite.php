<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 收藏模型
 */
class Favorite extends BaseModel
{
    protected $name = 'favorites';

    /**
     * 所属用户
     */
    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 收藏的内容
     */
    public function album(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Album::class, 'album_id');
    }
}
