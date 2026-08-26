<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\model\AdminLog;
use think\facade\Request;

/**
 * 后台操作日志服务
 */
class AdminLogService
{
    /**
     * 记录后台操作
     */
    public static function record(int $adminId, string $action, ?string $target = null): void
    {
        AdminLog::create([
            'admin_id' => $adminId,
            'action'   => $action,
            'target'   => $target,
            'ip'       => Request::ip(),
        ]);
    }
}
