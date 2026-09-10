<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\middleware\AdminAuth;
use app\common\model\FriendLink as FriendLinkModel;
use app\common\service\AdminLogService;
use think\response\Json;

/**
 * 后台友情链接管理
 */
class FriendLink extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 友链列表（后台用，全状态）
     */
    public function index(): Json
    {
        $list = FriendLinkModel::order('sort asc, id asc')->select();
        return json(['code' => 0, 'message' => 'ok', 'data' => $list->toArray()]);
    }

    /**
     * 创建或更新友链
     */
    public function save(): Json
    {
        $data = $this->request->post();
        $this->validate($data, [
            'name' => 'require|max:50',
            'url'  => 'require|max:255|url',
        ]);

        $id = (int) ($data['id'] ?? 0);
        $payload = [
            'name'   => (string) $data['name'],
            'url'    => (string) $data['url'],
            'sort'   => (int) ($data['sort'] ?? 0),
            'status' => (int) ($data['status'] ?? FriendLinkModel::STATUS_ENABLED),
        ];

        if ($id > 0) {
            FriendLinkModel::where('id', $id)->update($payload);
            AdminLogService::record((int) $this->request->currentAdmin->id, 'update_friend_link', (string) $id);
            return json(['code' => 0, 'message' => '更新成功', 'data' => ['id' => $id]]);
        }

        $new = FriendLinkModel::create($payload);
        AdminLogService::record((int) $this->request->currentAdmin->id, 'create_friend_link', (string) $new->id);
        return json(['code' => 0, 'message' => '创建成功', 'data' => ['id' => (int) $new->id]]);
    }

    /**
     * 删除友链
     */
    public function delete(int $id): Json
    {
        FriendLinkModel::where('id', $id)->delete();
        AdminLogService::record((int) $this->request->currentAdmin->id, 'delete_friend_link', (string) $id);
        return json(['code' => 0, 'message' => '删除成功', 'data' => null]);
    }

    /**
     * 切换启用/禁用状态
     */
    public function toggle(int $id): Json
    {
        $link = FriendLinkModel::find($id);
        if (!$link) {
            throw new BizException('友链不存在', 1801);
        }
        $newStatus = (int) $link->status === FriendLinkModel::STATUS_ENABLED
            ? FriendLinkModel::STATUS_DISABLED
            : FriendLinkModel::STATUS_ENABLED;
        $link->status = $newStatus;
        $link->save();
        AdminLogService::record((int) $this->request->currentAdmin->id, 'toggle_friend_link', (string) $id);
        return json(['code' => 0, 'message' => 'ok', 'data' => ['status' => $newStatus]]);
    }
}
