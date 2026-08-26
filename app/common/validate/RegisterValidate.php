<?php
declare(strict_types=1);

namespace app\common\validate;

use think\Validate;

/**
 * 邀请码注册验证器
 */
class RegisterValidate extends Validate
{
    protected $rule = [
        'username'    => 'require|min:3|max:20|alphaNum',
        'password'    => 'require|min:6|max:32',
        'invite_code' => 'require|alphaNum|length:6,32',
    ];

    protected $message = [
        'username.require'    => '请输入用户名',
        'username.min'        => '用户名至少 3 个字符',
        'username.max'        => '用户名最多 20 个字符',
        'username.alphaNum'   => '用户名只能包含字母和数字',
        'password.require'    => '请输入密码',
        'password.min'        => '密码至少 6 位',
        'password.max'        => '密码最多 32 位',
        'invite_code.require' => '请输入邀请码',
        'invite_code.length'  => '邀请码格式不正确',
    ];
}
