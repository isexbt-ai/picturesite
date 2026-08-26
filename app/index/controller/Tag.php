<?php
declare(strict_types=1);

namespace app\index\controller;

use app\BaseController;
use app\common\middleware\AuthWall;
use app\common\model\Category as CategoryModel;
use app\common\service\ContentService;
use think\response\View;

/**
 * 前台标签页
 */
class Tag extends BaseController
{
    protected $middleware = [AuthWall::class];

    public function index(string $slug): View
    {
        $user = $this->request->currentUser;
        $page = max(1, (int) $this->request->get('page', 1));
        $list = ContentService::paginateByTagSlug($user, $slug, $page, 12);

        return view('tag/index', [
            'user'       => $user,
            'list'       => $list,
            'categories' => CategoryModel::where('status', CategoryModel::STATUS_ENABLED)->order('sort asc, id asc')->select(),
        ]);
    }
}
