<?php
declare(strict_types=1);

namespace app\index\controller;

use app\BaseController;
use app\common\middleware\AuthWall;
use app\common\model\Category as CategoryModel;
use app\common\service\ContentService;
use think\response\View;

/**
 * 前台搜索页
 */
class Search extends BaseController
{
    protected $middleware = [AuthWall::class];

    public function index(): View
    {
        $user = $this->request->currentUser;
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page = max(1, (int) $this->request->get('page', 1));
        $list = ContentService::paginateByKeyword($user, $keyword, $page, 12);

        return view('search/index', [
            'user'       => $user,
            'keyword'    => $keyword,
            'list'       => $list,
            'categories' => CategoryModel::where('status', CategoryModel::STATUS_ENABLED)->order('sort asc, id asc')->select(),
        ]);
    }
}
