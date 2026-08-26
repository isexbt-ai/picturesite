<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 管理员模型
 */
class AdminUser extends BaseModel
{
    protected $name = 'admin_users';

    public const STATUS_NORMAL = 1;
    public const STATUS_BANNED = 0;

    /** 序列化时隐藏密码 */
    protected $hidden = ['password'];
}
