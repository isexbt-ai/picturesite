<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 友情链接模型
 */
class FriendLink extends BaseModel
{
    protected $name = 'friend_links';

    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;
}
