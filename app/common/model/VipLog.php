<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * VIP 开通记录模型
 */
class VipLog extends BaseModel
{
    protected $name = 'vip_logs';

    /** 开通来源 */
    public const SOURCE_CARD = 'card';
    public const SOURCE_MANUAL = 'manual';
}
