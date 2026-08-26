<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\model\AdminUser;
use app\common\service\AuthService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * 创建管理员账号
 * 用法：php think admin:create --user=admin --password=xxx
 */
class CreateAdmin extends Command
{
    protected function configure(): void
    {
        $this->setName('admin:create')
            ->addOption('user', null, Option::VALUE_REQUIRED, '管理员用户名')
            ->addOption('password', null, Option::VALUE_REQUIRED, '管理员密码')
            ->setDescription('创建管理员账号');
    }

    protected function execute(Input $input, Output $output): int
    {
        $username = (string) $input->getOption('user');
        $password = (string) $input->getOption('password');
        if ($username === '' || $password === '') {
            $output->error('必须提供 --user 与 --password');
            return 1;
        }
        if (AdminUser::where('username', $username)->find()) {
            $output->error('管理员已存在: ' . $username);
            return 1;
        }
        AdminUser::create([
            'username' => $username,
            'password' => AuthService::hashPassword($password),
            'status'   => AdminUser::STATUS_NORMAL,
        ]);
        $output->info('管理员创建成功: ' . $username);
        return 0;
    }
}
