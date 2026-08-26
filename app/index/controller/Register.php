<?php
declare(strict_types=1);

namespace app\index\controller;

use app\BaseController;
use app\common\service\AuthService;
use think\response\View;

/**
 * 前台注册页（无登录墙）
 */
class Register extends BaseController
{
    public function index(): \think\Response
    {
        if (AuthService::currentUser()) {
            return redirect('/');
        }
        return view('register/index');
    }
}
