<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\middleware\AdminAuth;
use app\common\model\Album;
use app\common\model\Category as CategoryModel;
use app\common\service\AdminLogService;
use think\response\Json;

/**
 * 后台分类管理
 */
class Category extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 分类列表
     */
    public function index(): Json
    {
        $list = CategoryModel::order('sort asc, id asc')->select();
        return json(['code' => 0, 'message' => 'ok', 'data' => $list->toArray()]);
    }

    /**
     * 创建或更新分类
     */
    public function save(): Json
    {
        $data = $this->request->post();
        $this->validate($data, [
            'name' => 'require|max:50',
            'slug' => 'require|alphaDash|max:50',
        ]);

        $id = (int) ($data['id'] ?? 0);
        $payload = [
            'name'      => $data['name'],
            'slug'      => $data['slug'],
            'parent_id' => (int) ($data['parent_id'] ?? 0),
            'sort'      => (int) ($data['sort'] ?? 0),
            'status'    => (int) ($data['status'] ?? CategoryModel::STATUS_ENABLED),
        ];

        if (CategoryModel::where('slug', $payload['slug'])->where('id', '<>', $id)->find()) {
            throw new BizException('slug 已存在', 1401);
        }

        if ($id > 0) {
            CategoryModel::where('id', $id)->update($payload);
            AdminLogService::record((int) $this->request->currentAdmin->id, 'update_category', (string) $id);
            return json(['code' => 0, 'message' => '更新成功', 'data' => ['id' => $id]]);
        }

        $new = CategoryModel::create($payload);
        AdminLogService::record((int) $this->request->currentAdmin->id, 'create_category', (string) $new->id);
        return json(['code' => 0, 'message' => '创建成功', 'data' => ['id' => (int) $new->id]]);
    }

    /**
     * 删除分类（有内容时禁止删除）
     */
    public function delete(int $id): Json
    {
        if (Album::where('category_id', $id)->find()) {
            throw new BizException('该分类下存在内容，无法删除', 1402);
        }
        CategoryModel::where('id', $id)->delete();
        AdminLogService::record((int) $this->request->currentAdmin->id, 'delete_category', (string) $id);
        return json(['code' => 0, 'message' => '删除成功', 'data' => null]);
    }
}
