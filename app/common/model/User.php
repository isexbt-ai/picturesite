<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 用户模型
 */
class User extends BaseModel
{
    protected $name = 'users';

    /** 状态 */
    public const STATUS_NORMAL = 1;
    public const STATUS_BANNED = 0;

    /** VIP 等级 */
    public const VIP_FREE = 0;
    public const VIP_V1 = 1;
    public const VIP_V2 = 2;
    public const VIP_V3 = 3;

    /** 序列化时隐藏敏感字段 */
    protected $hidden = ['password', 'salt'];

    /**
     * 收藏记录
     */
    public function favorites(): \think\model\relation\HasMany
    {
        return $this->hasMany(Favorite::class, 'user_id');
    }
}
