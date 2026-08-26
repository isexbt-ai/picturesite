<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BizException;
use app\common\model\Album;
use app\common\model\User;

/**
 * 内容访问控制服务：登录墙 + 内容分级校验
 * 分级规则：用户有效 VIP 等级 >= 内容 level 才可查看，游客不可见任何内容
 */
class ContentAccessService
{
    /** 游客等级（低于所有内容等级） */
    public const GUEST_LEVEL = -1;

    /**
     * 用户当前有效等级（VIP 过期实时按免费等级处理）
     */
    public static function effectiveLevel(?User $user): int
    {
        if ($user === null) {
            return self::GUEST_LEVEL;
        }
        $level = (int) $user->vip_level;
        if ($level > 0 && $user->vip_expire_at && strtotime((string) $user->vip_expire_at) < time()) {
            return User::VIP_FREE;
        }
        return $level;
    }

    /**
     * 校验用户可访问内容，不满足则抛业务异常
     */
    public static function assertAccess(?User $user, Album $album): void
    {
        if ((int) $album->status !== Album::STATUS_PUBLISHED) {
            throw new BizException('内容不存在或未发布', 1021);
        }
        if ($user === null || (int) $user->status !== User::STATUS_NORMAL) {
            throw new BizException('请先登录', 1022, 401);
        }
        if (self::effectiveLevel($user) < (int) $album->level) {
            throw new BizException('该内容需要更高会员等级', 1023, 403);
        }
    }

    /**
     * 是否能访问（不抛异常版本）
     */
    public static function canAccess(?User $user, Album $album): bool
    {
        try {
            self::assertAccess($user, $album);
            return true;
        } catch (BizException) {
            return false;
        }
    }
}
