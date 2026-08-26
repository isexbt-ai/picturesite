<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\middleware\AdminAuth;
use app\common\model\Album;
use app\common\model\Card;
use app\common\model\InviteCode;
use app\common\model\User;
use think\response\Json;

/**
 * 后台数据统计
 */
class Stats extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 仪表盘统计
     */
    public function dashboard(): Json
    {
        return json([
            'code'    => 0,
            'message' => 'ok',
            'data'    => [
                'album_total'  => (int) Album::count(),
                'album_type'   => [
                    'album'  => (int) Album::where('type', Album::TYPE_ALBUM)->count(),
                    'single' => (int) Album::where('type', Album::TYPE_SINGLE)->count(),
                    'video'  => (int) Album::where('type', Album::TYPE_VIDEO)->count(),
                ],
                'user_total'   => (int) User::count(),
                'vip_total'    => (int) User::where('vip_level', '>', User::VIP_FREE)->count(),
                'view_total'   => (int) Album::sum('view_count'),
                'card_used'    => (int) Card::where('status', Card::STATUS_USED)->count(),
                'card_unused'  => (int) Card::where('status', Card::STATUS_UNUSED)->count(),
                'invite_used'  => (int) InviteCode::where('status', InviteCode::STATUS_USED)->count(),
                'invite_total' => (int) InviteCode::count(),
            ],
        ]);
    }
}
