<?php
declare(strict_types=1);

namespace app\common\validate;

use think\Validate;

/**
 * 登录验证器
 */
class LoginValidate extends Validate
{
    protected $rule = [
        'username' => 'require',
        'password' => 'require',
    ];

    protected $message = [
        'username.require' => '请输入用户名',
        'password.require' => '请输入密码',
    ];
}
