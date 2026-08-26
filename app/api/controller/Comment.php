<?php
declare(strict_types=1);

namespace app\api\controller;

use app\BaseController;
use app\common\middleware\AuthWall;
use app\common\service\CommentService;
use think\response\Json;

/**
 * 前台评论接口
 */
class Comment extends BaseController
{
    protected $middleware = [AuthWall::class];

    /**
     * 发表评论
     */
    public function create(): Json
    {
        $data = $this->request->post();
        $this->validate($data, [
            'album_id' => 'require|number',
            'content'  => 'require',
        ]);
        $comment = CommentService::create($this->request->currentUser, (int) $data['album_id'], (string) $data['content']);
        return json([
            'code'    => 0,
            'message' => '评论成功',
            'data'    => ['id' => (int) $comment->id, 'status' => (int) $comment->status],
        ]);
    }

    /**
     * 评论列表
     */
    public function list(): Json
    {
        $albumId = (int) $this->request->get('album_id', 0);
        $page = max(1, (int) $this->request->get('page', 1));
        $result = CommentService::list($albumId, $page, 20);
        return json(['code' => 0, 'message' => 'ok', 'data' => $result]);
    }
}
