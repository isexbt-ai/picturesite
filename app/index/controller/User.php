<?php
declare(strict_types=1);

namespace app\index\controller;

use app\BaseController;
use app\common\middleware\AuthWall;
use app\common\model\BrowseLog;
use app\common\model\Category as CategoryModel;
use app\common\model\Favorite;
use app\common\service\StorageService;
use think\response\View;

/**
 * 前台个人中心：VIP 状态 / 收藏 / 浏览记录
 */
class User extends BaseController
{
    protected $middleware = [AuthWall::class];

    public function index(): View
    {
        $user = $this->request->currentUser;
        $uid = (int) $user->id;

        // 收藏（含封面完整 URL）
        $favorites = Favorite::with('album')->where('user_id', $uid)->order('id desc')->limit(12)->select()
            ->map(static fn($fav) => [
                'album_id'  => (int) $fav->album_id,
                'title'     => $fav->album ? (string) $fav->album->title : '',
                'cover_url' => $fav->album && $fav->album->cover ? StorageService::url((string) $fav->album->cover) : '',
            ]);

        // 浏览记录
        $browseLogs = BrowseLog::with('album')->where('user_id', $uid)->order('last_view_at desc')->limit(12)->select()
            ->map(static fn($b) => [
                'album_id' => (int) $b->album_id,
                'title'    => $b->album ? (string) $b->album->title : '',
                'view_at'  => (string) $b->last_view_at,
            ]);

        return view('user/index', [
            'user'       => $user,
            'favorites'  => $favorites,
            'browseLogs' => $browseLogs,
            'categories' => CategoryModel::where('status', CategoryModel::STATUS_ENABLED)->order('sort asc, id asc')->select(),
        ]);
    }
}
