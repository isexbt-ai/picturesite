<?php
declare(strict_types=1);

namespace app\common\command;

use app\common\service\VipService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * VIP 到期自动降级任务
 * 用法：php think vip:expire-check
 */
class VipExpireCheck extends Command
{
    protected function configure(): void
    {
        $this->setName('vip:expire-check')
            ->setDescription('检测 VIP 到期并将过期用户降级为免费');
    }

    protected function execute(Input $input, Output $output): int
    {
        $count = VipService::expireDowngrade();
        $output->info('到期降级完成，处理 ' . $count . ' 个用户');
        return 0;
    }
}
