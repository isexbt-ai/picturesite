<?php
declare(strict_types=1);

namespace app\api\controller;

use app\BaseController;
use app\common\middleware\AuthWall;
use app\common\service\AuthService;
use app\common\service\CardService;
use think\response\Json;

/**
 * 前台卡密兑换接口
 */
class Card extends BaseController
{
    protected $middleware = [AuthWall::class];

    /**
     * 兑换卡密
     */
    public function redeem(): Json
    {
        $data = $this->request->post();
        $this->validate($data, ['code' => 'require']);
        $user = $this->request->currentUser;
        $result = CardService::redeem($user, trim((string) $data['code']));
        return json([
            'code'    => 0,
            'message' => '兑换成功',
            'data'    => $result,
        ]);
    }

    /**
     * 当前 VIP 状态（个人中心展示）
     */
    public function status(): Json
    {
        $user = AuthService::currentUser();
        return json([
            'code'    => 0,
            'message' => 'ok',
            'data'    => [
                'vip_level'     => (int) $user->vip_level,
                'vip_expire_at' => $user->vip_expire_at,
            ],
        ]);
    }
}
