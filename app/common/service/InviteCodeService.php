<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\InviteCode;

/**
 * 邀请码服务：随机生成（去混淆字符）与批量创建
 */
class InviteCodeService
{
    /** 生成字符集：去除易混淆的 0/O/1/I */
    private const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * 批量生成邀请码
     *
     * @param int         $count    生成数量
     * @param string|null $expireAt 过期时间 Y-m-d H:i:s，null 为永不过期
     * @return int 实际生成数量
     */
    public static function generate(int $count, ?string $expireAt = null): int
    {
        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $code = self::randomUniqueCode();
            if ($code === '') {
                continue; // 连续冲突过多时跳过
            }
            InviteCode::create([
                'code'      => $code,
                'status'    => InviteCode::STATUS_UNUSED,
                'expire_at' => $expireAt,
            ]);
            $created++;
        }
        return $created;
    }

    /**
     * 生成不重复的随机邀请码（最多重试 50 次）
     */
    public static function randomUniqueCode(int $length = 8): string
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $code = self::randomCode($length);
            if (!InviteCode::where('code', $code)->find()) {
                return $code;
            }
        }
        return '';
    }

    /**
     * 纯随机码（不查重）
     */
    public static function randomCode(int $length = 8): string
    {
        $chars = self::CHARS;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
}
