<?php
declare(strict_types=1);

namespace app\api\controller;

use app\BaseController;
use app\common\middleware\AuthWall;
use app\common\service\FavoriteService;
use think\response\Json;

/**
 * 前台收藏接口
 */
class Favorite extends BaseController
{
    protected $middleware = [AuthWall::class];

    /**
     * 切换收藏
     */
    public function toggle(): Json
    {
        $data = $this->request->post();
        $this->validate($data, ['album_id' => 'require|number']);
        $result = FavoriteService::toggle($this->request->currentUser, (int) $data['album_id']);
        return json(['code' => 0, 'message' => $result['favorited'] ? '已收藏' : '已取消收藏', 'data' => $result]);
    }

    /**
     * 收藏列表
     */
    public function list(): Json
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $result = FavoriteService::list($this->request->currentUser, $page, 12);
        return json(['code' => 0, 'message' => 'ok', 'data' => $result]);
    }
}
