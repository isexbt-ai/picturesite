<?php
declare(strict_types=1);

namespace app\api\controller;

use app\BaseController;

/**
 * 系统健康检查接口（M1 占位，验证 api 应用路由与 JSON 返回）
 */
class Health extends BaseController
{
    /**
     * 健康检查
     */
    public function check(): \think\response\Json
    {
        return json([
            'code'    => 0,
            'message' => 'ok',
            'data'    => [
                'time'    => date('Y-m-d H:i:s'),
                'php'     => PHP_VERSION,
                'version' => \think\facade\App::version(),
            ],
        ]);
    }
}
