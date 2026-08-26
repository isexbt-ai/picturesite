<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 卡密批次模型
 */
class CardBatch extends BaseModel
{
    protected $name = 'card_batches';

    /**
     * 批次下的卡密
     */
    public function cards(): \think\model\relation\HasMany
    {
        return $this->hasMany(Card::class, 'batch_id');
    }
}
