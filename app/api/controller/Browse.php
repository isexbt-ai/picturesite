<?php
declare(strict_types=1);

namespace app\api\controller;

use app\BaseController;
use app\common\middleware\AuthWall;
use app\common\service\BrowseLogService;
use think\response\Json;

/**
 * 前台浏览记录接口
 */
class Browse extends BaseController
{
    protected $middleware = [AuthWall::class];

    /**
     * 浏览记录列表
     */
    public function list(): Json
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $result = BrowseLogService::list($this->request->currentUser, $page, 12);
        return json(['code' => 0, 'message' => 'ok', 'data' => $result]);
    }
}
