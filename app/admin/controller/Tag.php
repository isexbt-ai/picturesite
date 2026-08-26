<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\middleware\AdminAuth;
use app\common\model\AlbumTag;
use app\common\model\Tag as TagModel;
use app\common\service\AdminLogService;
use think\response\Json;

/**
 * 后台标签管理
 */
class Tag extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 标签列表
     */
    public function index(): Json
    {
        $list = TagModel::order('id asc')->select();
        return json(['code' => 0, 'message' => 'ok', 'data' => $list->toArray()]);
    }

    /**
     * 创建或更新标签
     */
    public function save(): Json
    {
        $data = $this->request->post();
        $this->validate($data, [
            'name' => 'require|max:50',
            'slug' => 'require|alphaDash|max:50',
        ]);

        $id = (int) ($data['id'] ?? 0);
        $payload = ['name' => $data['name'], 'slug' => $data['slug']];

        if (TagModel::where('slug', $payload['slug'])->where('id', '<>', $id)->find()) {
            throw new BizException('slug 已存在', 1501);
        }

        if ($id > 0) {
            TagModel::where('id', $id)->update($payload);
            AdminLogService::record((int) $this->request->currentAdmin->id, 'update_tag', (string) $id);
            return json(['code' => 0, 'message' => '更新成功', 'data' => ['id' => $id]]);
        }

        $new = TagModel::create($payload);
        AdminLogService::record((int) $this->request->currentAdmin->id, 'create_tag', (string) $new->id);
        return json(['code' => 0, 'message' => '创建成功', 'data' => ['id' => (int) $new->id]]);
    }

    /**
     * 删除标签（同时清理关联）
     */
    public function delete(int $id): Json
    {
        TagModel::where('id', $id)->delete();
        AlbumTag::where('tag_id', $id)->delete();
        AdminLogService::record((int) $this->request->currentAdmin->id, 'delete_tag', (string) $id);
        return json(['code' => 0, 'message' => '删除成功', 'data' => null]);
    }
}
