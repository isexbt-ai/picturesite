<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BizException;
use app\common\model\Card;
use app\common\model\CardBatch;
use app\common\model\User;
use app\common\model\VipLog;
use think\facade\Db;

/**
 * 卡密服务：批次生成 / 兑换 / 卡密导出
 */
class CardService
{
    /** 卡密字符集（去易混淆 0/O/1/I） */
    private const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * 创建批次并生成卡密
     *
     * @param string $name    批次名称
     * @param int    $level   开通等级 1-3
     * @param int    $days    有效天数
     * @param int    $total   生成数量
     */
    public static function createBatch(string $name, int $level, int $days, int $total): CardBatch
    {
        if ($level < 1 || $level > 3) {
            throw new BizException('等级需为 1-3', 1901);
        }
        if ($days < 1 || $days > 3650) {
            throw new BizException('天数需在 1-3650 之间', 1902);
        }
        $total = max(1, min(10000, $total));

        return Db::transaction(function () use ($name, $level, $days, $total) {
            $batch = CardBatch::create([
                'name'          => $name,
                'level'         => $level,
                'duration_days' => $days,
                'total'         => $total,
            ]);

            $codes = [];
            for ($i = 0; $i < $total; $i++) {
                $codes[] = [
                    'batch_id'      => (int) $batch->id,
                    'code'          => self::uniqueCardCode(),
                    'level'         => $level,
                    'duration_days' => $days,
                    'status'        => Card::STATUS_UNUSED,
                ];
            }
            Db::name('cards')->insertAll($codes);

            return $batch;
        });
    }

    /**
     * 用户兑换卡密
     *
     * @return array{new_level:int, vip_expire_at:string}
     */
    public static function redeem(User $user, string $code): array
    {
        return Db::transaction(function () use ($user, $code) {
            $card = Card::where('code', $code)->lock(true)->find();
            if (!$card || (int) $card->status !== Card::STATUS_UNUSED) {
                throw new BizException('卡密无效或已被使用', 1903);
            }

            $result = VipService::grant($user, (int) $card->level, (int) $card->duration_days, VipLog::SOURCE_CARD, (int) $card->id);

            $card->status  = Card::STATUS_USED;
            $card->used_by = (int) $user->id;
            $card->used_at = date('Y-m-d H:i:s');
            $card->save();

            return $result;
        });
    }

    /**
     * 生成不重复卡密（格式 XXXX-XXXX-XXXX-XXXX）
     */
    public static function uniqueCardCode(): string
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $code = self::randomBlock() . '-' . self::randomBlock() . '-' . self::randomBlock() . '-' . self::randomBlock();
            if (!Card::where('code', $code)->find()) {
                return $code;
            }
        }
        throw new \RuntimeException('卡密生成失败，请重试');
    }

    /**
     * 随机 4 位块
     */
    private static function randomBlock(): string
    {
        $block = '';
        for ($i = 0; $i < 4; $i++) {
            $block .= self::CHARS[random_int(0, strlen(self::CHARS) - 1)];
        }
        return $block;
    }
}
