<?php
declare(strict_types=1);

namespace app\api\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\model\User;
use app\common\service\AuthService;
use think\response\Json;

/**
 * 前台认证接口：注册 / 登录 / 登出 / 当前用户
 */
class Auth extends BaseController
{
    /**
     * 邀请码注册
     */
    public function register(): Json
    {
        $data = $this->request->post();
        $this->validate($data, \app\common\validate\RegisterValidate::class);
        $user = AuthService::register($data['username'], $data['password'], $data['invite_code']);
        return json(['code' => 0, 'message' => '注册成功', 'data' => ['id' => (int) $user->id]]);
    }

    /**
     * 登录
     */
    public function login(): Json
    {
        $data = $this->request->post();
        $this->validate($data, \app\common\validate\LoginValidate::class);
        $user = AuthService::login($data['username'], $data['password']);
        return json(['code' => 0, 'message' => '登录成功', 'data' => self::userPayload($user)]);
    }

    /**
     * 登出
     */
    public function logout(): Json
    {
        AuthService::logout();
        return json(['code' => 0, 'message' => '已退出登录', 'data' => null]);
    }

    /**
     * 当前用户信息
     */
    public function me(): Json
    {
        $user = AuthService::currentUser();
        if ($user === null) {
            throw new BizException('请先登录', 401, 401);
        }
        return json(['code' => 0, 'message' => 'ok', 'data' => self::userPayload($user)]);
    }

    /**
     * 用户信息载荷
     */
    public static function userPayload(User $user): array
    {
        return [
            'id'             => (int) $user->id,
            'username'       => $user->username,
            'vip_level'      => (int) $user->vip_level,
            'vip_expire_at'  => $user->vip_expire_at,
            'created_at'     => $user->create_time,
        ];
    }
}
