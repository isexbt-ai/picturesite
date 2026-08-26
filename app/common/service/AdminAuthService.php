<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BizException;
use app\common\model\AdminUser;
use think\facade\Cache;
use think\facade\Request;

/**
 * 管理员认证服务：登录签发 Token（后台 Vue SPA 使用 Bearer Token）
 */
class AdminAuthService
{
    public const TOKEN_PREFIX = 'admin_token:';
    public const TOKEN_TTL = 86400;

    /**
     * 登录，成功返回令牌
     */
    public static function login(string $username, string $password): string
    {
        $admin = AdminUser::where('username', $username)->find();
        if (!$admin || !password_verify($password, (string) $admin->password)) {
            throw new BizException('用户名或密码错误', 1101);
        }
        if ((int) $admin->status !== AdminUser::STATUS_NORMAL) {
            throw new BizException('账号已被禁用', 1102);
        }

        $token = bin2hex(random_bytes(32));
        Cache::set(self::TOKEN_PREFIX . $token, (int) $admin->id, self::TOKEN_TTL);

        $admin->last_login_at = date('Y-m-d H:i:s');
        $admin->save();

        return $token;
    }

    /**
     * 登出（清除令牌）
     */
    public static function logout(): void
    {
        $token = self::requestToken();
        if ($token !== '') {
            Cache::delete(self::TOKEN_PREFIX . $token);
        }
    }

    /**
     * 当前登录管理员，未登录返回 null
     */
    public static function currentAdmin(): ?AdminUser
    {
        $token = self::requestToken();
        if ($token === '') {
            return null;
        }
        $id = Cache::get(self::TOKEN_PREFIX . $token);
        if (!$id) {
            return null;
        }
        $admin = AdminUser::find((int) $id);
        return $admin && (int) $admin->status === AdminUser::STATUS_NORMAL ? $admin : null;
    }

    /**
     * 从 Authorization 头提取 Bearer Token
     */
    private static function requestToken(): string
    {
        $auth = Request::header('Authorization', '');
        return (string) preg_replace('/^Bearer\s+/i', '', $auth);
    }
}
