<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 评论模型
 */
class Comment extends BaseModel
{
    protected $name = 'comments';

    public const STATUS_VISIBLE = 1;
    public const STATUS_HIDDEN = 0;

    /**
     * 所属用户
     */
    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 所属内容
     */
    public function album(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Album::class, 'album_id');
    }
}
