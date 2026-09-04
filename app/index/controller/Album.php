<?php
declare(strict_types=1);

namespace app\index\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\middleware\AuthWall;
use app\common\model\Album as AlbumModel;
use app\common\model\Category;
use app\common\service\ContentService;
use think\response\View;

/**
 * 前台内容详情：图集灯箱 / 单图 / 视频播放
 */
class Album extends BaseController
{
    protected $middleware = [AuthWall::class];

    public function detail(int $id): View
    {
        $user = $this->request->currentUser;
        $album = AlbumModel::with(['category', 'tags', 'images', 'video'])->find($id);
        if (!$album) {
            throw new BizException('内容不存在', 1021);
        }
        // 仅校验已发布；分级校验由 detailPayload 内部裁剪媒体 URL 实现（不抛异常，保证低等级也能进入详情页看到封面+提示）
        if ((int) $album->status !== \app\common\model\Album::STATUS_PUBLISHED) {
            throw new BizException('内容不存在或未发布', 1021);
        }
        ContentService::incrementView($album);
        \app\common\service\BrowseLogService::record($user, (int) $album->id);

        $payload = ContentService::detailPayload($album, $user);
        return view('album/detail', [
            'user'       => $user,
            'album'      => $payload,
            'imagesJSON' => json_encode($payload['images'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'favorited'  => \app\common\service\FavoriteService::hasFavorited($user, (int) $album->id),
            'comments'   => \app\common\service\CommentService::list((int) $album->id, 1, 20),
            'categories' => Category::where('status', Category::STATUS_ENABLED)->order('sort asc, id asc')->select(),
        ]);
    }
}
