<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\service\AdminAuthService;
use think\response\Json;

/**
 * 后台认证接口（登录/登出/当前管理员）
 */
class Auth extends BaseController
{
    /**
     * 登录，返回 Bearer Token
     */
    public function login(): Json
    {
        $data = $this->request->post();
        $this->validate($data, \app\common\validate\AdminLoginValidate::class);
        $token = AdminAuthService::login($data['username'], $data['password']);
        return json(['code' => 0, 'message' => '登录成功', 'data' => ['token' => $token]]);
    }

    /**
     * 登出
     */
    public function logout(): Json
    {
        AdminAuthService::logout();
        return json(['code' => 0, 'message' => 'ok', 'data' => null]);
    }

    /**
     * 当前管理员信息
     */
    public function me(): Json
    {
        $admin = AdminAuthService::currentAdmin();
        if ($admin === null) {
            throw new BizException('未登录或登录已过期', 401, 401);
        }
        return json([
            'code'    => 0,
            'message' => 'ok',
            'data'    => [
                'id'       => (int) $admin->id,
                'username' => $admin->username,
            ],
        ]);
    }
}
