<?php
declare(strict_types=1);

namespace app\common\middleware;

use app\common\service\AdminAuthService;
use Closure;
use think\Request;
use think\Response;

/**
 * 后台鉴权中间件：校验 Bearer Token
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = AdminAuthService::currentAdmin();
        if ($admin === null) {
            return json(['code' => 401, 'message' => '未登录或登录已过期', 'data' => null], 401);
        }
        $request->currentAdmin = $admin;
        return $next($request);
    }
}
