<?php
declare(strict_types=1);

namespace app\index\controller;

use app\BaseController;
use app\common\service\AuthService;
use think\response\View;

/**
 * 前台登录页（无登录墙）
 */
class Login extends BaseController
{
    public function index(): \think\Response
    {
        // 已登录则回首页
        if (AuthService::currentUser()) {
            return redirect('/');
        }
        return view('login/index');
    }
}
