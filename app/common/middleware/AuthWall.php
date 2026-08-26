<?php
declare(strict_types=1);

namespace app\common\middleware;

use app\common\service\AuthService;
use Closure;
use think\Request;
use think\Response;

/**
 * 登录墙中间件：未登录用户拦截
 * JSON 请求返回 401，否则跳转登录页
 */
class AuthWall
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = AuthService::currentUser();
        if ($user === null) {
            if ($request->isJson()) {
                return json(['code' => 401, 'message' => '请先登录', 'data' => null], 401);
            }
            return redirect('/login');
        }
        // 注入当前用户，供控制器使用
        $request->currentUser = $user;
        return $next($request);
    }
}
