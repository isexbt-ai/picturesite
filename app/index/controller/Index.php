<?php
declare(strict_types=1);

namespace app\index\controller;

use app\BaseController;
use app\common\middleware\AuthWall;
use app\common\model\Category;
use app\common\service\ContentService;
use think\response\View;

/**
 * 前台首页：内容瀑布流
 */
class Index extends BaseController
{
    protected $middleware = [AuthWall::class];

    public function index(): View
    {
        $user = $this->request->currentUser;
        $page = max(1, (int) $this->request->get('page', 1));
        $list = ContentService::paginateCards($user, $page, 12);

        return view('index/index', [
            'user'       => $user,
            'list'       => $list,
            'categories' => Category::where('status', Category::STATUS_ENABLED)->order('sort asc, id asc')->select(),
        ]);
    }
}
