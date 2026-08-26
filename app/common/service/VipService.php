<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BizException;
use app\common\model\User;
use app\common\model\VipLog;
use think\facade\Db;

/**
 * VIP 服务：发放 / 到期降级
 */
class VipService
{
    /**
     * 发放或续期 VIP
     *
     * @param User   $user       用户
     * @param int    $level      开通等级 1-3
     * @param int    $days       有效天数
     * @param string $source     来源 card/manual
     * @param int|null $cardId   卡密 ID
     * @param string $remark     备注
     * @return array{new_level:int, vip_expire_at:string}
     */
    public static function grant(User $user, int $level, int $days, string $source, ?int $cardId = null, string $remark = ''): array
    {
        if ($level < 1 || $level > 3) {
            throw new BizException('等级需为 1-3', 1911);
        }
        if ($days < 1) {
            throw new BizException('天数需大于 0', 1912);
        }

        return Db::transaction(function () use ($user, $level, $days, $source, $cardId, $remark) {
            $now = time();
            $base = 0;
            if ($user->vip_expire_at && strtotime((string) $user->vip_expire_at) > $now) {
                $base = strtotime((string) $user->vip_expire_at); // 未过期则在原到期基础上续期
            } else {
                $base = $now;
            }

            $newLevel = max((int) $user->vip_level, $level); // 只升不降
            $newExpire = date('Y-m-d H:i:s', $base + $days * 86400);

            $user->vip_level = $newLevel;
            $user->vip_expire_at = $newExpire;
            $user->save();

            VipLog::create([
                'user_id'       => (int) $user->id,
                'level'         => $newLevel,
                'duration_days' => $days,
                'source'        => $source,
                'card_id'       => $cardId,
                'remark'        => $remark,
            ]);

            return ['new_level' => $newLevel, 'vip_expire_at' => $newExpire];
        });
    }

    /**
     * 批量处理到期降级（定时任务调用）
     *
     * @return int 降级用户数
     */
    public static function expireDowngrade(): int
    {
        $now = date('Y-m-d H:i:s');
        $count = User::where('vip_level', '>', User::VIP_FREE)
            ->whereNotNull('vip_expire_at')
            ->where('vip_expire_at', '<', $now)
            ->count();
        if ($count > 0) {
            User::where('vip_level', '>', User::VIP_FREE)
                ->whereNotNull('vip_expire_at')
                ->where('vip_expire_at', '<', $now)
                ->update(['vip_level' => User::VIP_FREE, 'vip_expire_at' => null]);
        }
        return $count;
    }
}
