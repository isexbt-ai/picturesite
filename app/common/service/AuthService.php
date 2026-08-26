<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BizException;
use app\common\model\InviteCode;
use app\common\model\User;
use think\facade\Db;
use think\facade\Session;

/**
 * 用户认证服务：邀请码注册 / 登录 / 登出 / 当前用户
 * 前台（index SSR + api AJAX）统一使用 session 维持登录态
 */
class AuthService
{
    public const SESSION_KEY = 'user_id';

    /**
     * 密码哈希（bcrypt，自带盐）
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 校验密码
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * 邀请码注册（事务内创建用户并核销邀请码）
     */
    public static function register(string $username, string $password, string $inviteCode): User
    {
        return Db::transaction(function () use ($username, $password, $inviteCode) {
            if (User::where('username', $username)->find()) {
                throw new BizException('用户名已存在', 1003);
            }

            $invite = InviteCode::where('code', $inviteCode)
                ->where('status', InviteCode::STATUS_UNUSED)
                ->find();
            if (!$invite) {
                throw new BizException('邀请码无效或已被使用', 1001);
            }
            if ($invite->expire_at && strtotime((string) $invite->expire_at) < time()) {
                throw new BizException('邀请码已过期', 1002);
            }

            $user = User::create([
                'username'         => $username,
                'password'         => self::hashPassword($password),
                'salt'             => '',
                'vip_level'        => User::VIP_FREE,
                'status'           => User::STATUS_NORMAL,
                'invite_code_used' => $inviteCode,
            ]);

            $invite->status  = InviteCode::STATUS_USED;
            $invite->used_by = (int) $user->id;
            $invite->used_at = date('Y-m-d H:i:s');
            $invite->save();

            return $user;
        });
    }

    /**
     * 登录，成功写入 session 并返回用户
     */
    public static function login(string $username, string $password): User
    {
        $user = User::where('username', $username)->find();
        if (!$user || !self::verifyPassword($password, (string) $user->password)) {
            throw new BizException('用户名或密码错误', 1011);
        }
        if ((int) $user->status !== User::STATUS_NORMAL) {
            throw new BizException('账号已被禁用', 1012);
        }

        Session::set(self::SESSION_KEY, (int) $user->id);
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->save();

        return $user;
    }

    /**
     * 登出（清空会话）
     */
    public static function logout(): void
    {
        Session::delete(self::SESSION_KEY);
    }

    /**
     * 当前登录用户，未登录返回 null
     */
    public static function currentUser(): ?User
    {
        $id = Session::get(self::SESSION_KEY);
        if (!$id) {
            return null;
        }
        $user = User::find((int) $id);
        return $user && (int) $user->status === User::STATUS_NORMAL ? $user : null;
    }
}
