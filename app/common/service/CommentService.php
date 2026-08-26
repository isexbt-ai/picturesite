<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BizException;
use app\common\model\Album;
use app\common\model\Comment;
use app\common\model\Setting;
use app\common\model\User;
use think\facade\Db;

/**
 * 评论服务
 */
class CommentService
{
    /**
     * 发表评论（评论开关关闭时禁止）
     */
    public static function create(User $user, int $albumId, string $content): Comment
    {
        if (Setting::getValue('comment_enabled', '1') !== '1') {
            throw new BizException('评论功能已关闭', 2301);
        }
        $content = trim($content);
        if ($content === '' || mb_strlen($content) > 500) {
            throw new BizException('评论内容需在 1-500 字之间', 2302);
        }
        if (!Album::where('id', $albumId)->find()) {
            throw new BizException('内容不存在', 2303);
        }

        $autoApprove = Setting::getValue('comment_auto_approve', '1');
        return Db::transaction(function () use ($user, $albumId, $content, $autoApprove) {
            return Comment::create([
                'album_id' => $albumId,
                'user_id'  => (int) $user->id,
                'content'  => $content,
                'status'   => $autoApprove === '1' ? Comment::STATUS_VISIBLE : Comment::STATUS_HIDDEN,
            ]);
        });
    }

    /**
     * 评论列表（仅显示可见评论）
     */
    public static function list(int $albumId, int $page = 1, int $size = 20): array
    {
        $list = Comment::with('user')
            ->where('album_id', $albumId)
            ->where('status', Comment::STATUS_VISIBLE)
            ->order('id desc')
            ->paginate(['list_rows' => $size, 'page' => $page]);

        return [
            'items' => $list->items(),
            'total' => $list->total(),
            'page'  => $page,
        ];
    }
}
