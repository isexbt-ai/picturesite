<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;

/**
 * 后台入口控制器（M1 占位，M3 接入 Vue3 后台后改为鉴权接口）
 */
class Index extends BaseController
{
    /**
     * 后台服务入口
     */
    public function index(): \think\response\Json
    {
        return json([
            'code'    => 0,
            'message' => 'admin api ok',
        ]);
    }
}
