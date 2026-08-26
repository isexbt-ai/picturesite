<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 邀请码模型
 */
class InviteCode extends BaseModel
{
    protected $name = 'invite_codes';

    public const STATUS_UNUSED = 0;
    public const STATUS_USED = 1;
    public const STATUS_DISABLED = 2;
}
