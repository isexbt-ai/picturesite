<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 卡密模型
 */
class Card extends BaseModel
{
    protected $name = 'cards';

    public const STATUS_UNUSED = 0;
    public const STATUS_USED = 1;
    public const STATUS_DISABLED = 2;

    /**
     * 所属批次
     */
    public function batch(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(CardBatch::class, 'batch_id');
    }
}
