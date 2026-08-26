<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\middleware\AdminAuth;
use app\common\model\User as UserModel;
use app\common\service\AdminLogService;
use app\common\service\VipService;
use think\response\Json;

/**
 * 后台 VIP 手动发放
 */
class Vip extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 手动发放 VIP
     */
    public function grant(): Json
    {
        $data = $this->request->post();
        $this->validate($data, [
            'username' => 'require',
            'level'    => 'require|number|between:1,3',
            'days'     => 'require|number',
        ]);

        $user = UserModel::where('username', $data['username'])->find();
        if (!$user) {
            throw new BizException('用户不存在', 2101);
        }

        $result = VipService::grant(
            $user,
            (int) $data['level'],
            (int) $data['days'],
            \app\common\model\VipLog::SOURCE_MANUAL,
            null,
            (string) ($data['remark'] ?? '')
        );
        AdminLogService::record((int) $this->request->currentAdmin->id, 'grant_vip', (string) $user->id);
        return json(['code' => 0, 'message' => '发放成功', 'data' => $result]);
    }
}
