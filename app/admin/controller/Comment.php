<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\middleware\AdminAuth;
use app\common\model\Comment as CommentModel;
use app\common\service\AdminLogService;
use think\response\Json;

/**
 * 后台评论审核
 */
class Comment extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 评论列表（分页 + 状态过滤）
     */
    public function index(): Json
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $size = min(100, max(1, (int) $this->request->get('size', 20)));
        $query = CommentModel::with(['user', 'album'])->order('id desc');

        if ($this->request->get('status', '') !== '') {
            $query->where('status', (int) $this->request->get('status'));
        }

        $list = $query->paginate(['list_rows' => $size, 'page' => $page]);
        return json([
            'code'    => 0,
            'message' => 'ok',
            'data'    => [
                'items' => $list->items(),
                'total' => $list->total(),
                'page'  => $page,
            ],
        ]);
    }

    /**
     * 显示/隐藏评论
     */
    public function toggle(int $id): Json
    {
        $comment = CommentModel::find($id);
        if (!$comment) {
            throw new BizException('评论不存在', 2401);
        }
        $comment->status = (int) $comment->status === CommentModel::STATUS_VISIBLE
            ? CommentModel::STATUS_HIDDEN
            : CommentModel::STATUS_VISIBLE;
        $comment->save();
        AdminLogService::record((int) $this->request->currentAdmin->id, 'toggle_comment', (string) $id);
        return json(['code' => 0, 'message' => '操作成功', 'data' => ['status' => (int) $comment->status]]);
    }

    /**
     * 删除评论
     */
    public function delete(int $id): Json
    {
        CommentModel::where('id', $id)->delete();
        AdminLogService::record((int) $this->request->currentAdmin->id, 'delete_comment', (string) $id);
        return json(['code' => 0, 'message' => '删除成功', 'data' => null]);
    }
}
