<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\middleware\AdminAuth;
use app\common\model\User as UserModel;
use think\response\Json;

/**
 * 后台用户管理
 */
class User extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 用户列表（分页 + 关键词过滤）
     */
    public function index(): Json
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $size = min(100, max(1, (int) $this->request->get('size', 20)));
        $query = UserModel::field('id,username,email,vip_level,vip_expire_at,status,invite_code_used,last_login_at,create_time')
            ->order('id desc');

        $keyword = trim((string) $this->request->get('keyword', ''));
        if ($keyword !== '') {
            $query->where('username', 'like', '%' . $keyword . '%');
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
}
