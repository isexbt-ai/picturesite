<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 浏览记录模型
 */
class BrowseLog extends BaseModel
{
    protected $name = 'browse_logs';

    /** 表无自动时间戳，改为手动维护 last_view_at */
    protected $autoWriteTimestamp = false;

    /**
     * 所属用户
     */
    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 浏览的内容
     */
    public function album(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Album::class, 'album_id');
    }
}
