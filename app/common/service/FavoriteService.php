<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BizException;
use app\common\model\Album;
use app\common\model\Favorite;
use app\common\model\User;
use think\Paginator;

/**
 * 收藏服务
 */
class FavoriteService
{
    /**
     * 切换收藏状态
     *
     * @return array{favorited:bool}
     */
    public static function toggle(User $user, int $albumId): array
    {
        if (!Album::where('id', $albumId)->find()) {
            throw new BizException('内容不存在', 2201);
        }
        $exists = Favorite::where('user_id', (int) $user->id)->where('album_id', $albumId)->find();
        if ($exists) {
            $exists->delete();
            return ['favorited' => false];
        }
        Favorite::create(['user_id' => (int) $user->id, 'album_id' => $albumId]);
        return ['favorited' => true];
    }

    /**
     * 收藏列表（分页）
     */
    public static function list(User $user, int $page = 1, int $size = 12): array
    {
        $list = Favorite::with(['album' => function ($q) {
            $q->with(['category', 'tags']);
        }])->where('user_id', (int) $user->id)->order('id desc')
            ->paginate(['list_rows' => $size, 'page' => $page]);

        return [
            'items' => $list->items(),
            'total' => $list->total(),
            'page'  => $page,
        ];
    }

    /**
     * 是否已收藏
     */
    public static function hasFavorited(User $user, int $albumId): bool
    {
        return (bool) Favorite::where('user_id', (int) $user->id)->where('album_id', $albumId)->find();
    }
}
