<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\BrowseLog;
use app\common\model\User;

/**
 * 浏览记录服务
 */
class BrowseLogService
{
    /**
     * 记录一次浏览（同内容更新时间，不重复插行）
     */
    public static function record(User $user, int $albumId): void
    {
        $now = date('Y-m-d H:i:s');
        $row = BrowseLog::where('user_id', (int) $user->id)->where('album_id', $albumId)->find();
        if ($row) {
            $row->last_view_at = $now;
            $row->save();
        } else {
            BrowseLog::create(['user_id' => (int) $user->id, 'album_id' => $albumId, 'last_view_at' => $now]);
        }
    }

    /**
     * 浏览记录列表（分页，按最近浏览排序）
     */
    public static function list(User $user, int $page = 1, int $size = 12): array
    {
        $list = BrowseLog::with(['album' => function ($q) {
            $q->with(['category', 'tags']);
        }])->where('user_id', (int) $user->id)->order('last_view_at desc')
            ->paginate(['list_rows' => $size, 'page' => $page]);

        return [
            'items' => $list->items(),
            'total' => $list->total(),
            'page'  => $page,
        ];
    }
}
