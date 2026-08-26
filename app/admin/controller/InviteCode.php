<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\middleware\AdminAuth;
use app\common\model\InviteCode as InviteCodeModel;
use app\common\service\AdminLogService;
use app\common\service\InviteCodeService;
use think\response\Json;

/**
 * 后台邀请码管理
 */
class InviteCode extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 邀请码列表（分页 + 状态过滤）
     */
    public function index(): Json
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $size = min(100, max(1, (int) $this->request->get('size', 20)));
        $query = InviteCodeModel::order('id desc');

        if ($this->request->get('status', '') !== '') {
            $query->where('status', (int) $this->request->get('status'));
        }

        $list = $query->paginate(['list_rows' => $size, 'page' => $page]);
        return json([
            'code'    => 0,
            'message' => 'ok',
            'data'    => [
                'items' => $list->items(),
                'total' => $list->total(),
                'page'  => $page,
            ],
        ]);
    }

    /**
     * 批量生成邀请码
     */
    public function generate(): Json
    {
        $count = max(1, min(500, (int) $this->request->post('count', 10)));
        $expireAt = $this->request->post('expire_at', null);
        $created = InviteCodeService::generate($count, $expireAt ? (string) $expireAt : null);
        AdminLogService::record((int) $this->request->currentAdmin->id, 'generate_invite_codes', (string) $created);
        return json(['code' => 0, 'message' => '生成成功', 'data' => ['count' => $created]]);
    }

    /**
     * 禁用/启用邀请码
     */
    public function toggle(int $id): Json
    {
        $code = InviteCodeModel::find($id);
        if (!$code) {
            throw new BizException('邀请码不存在', 1801);
        }
        $newStatus = (int) $code->status === InviteCodeModel::STATUS_DISABLED
            ? InviteCodeModel::STATUS_UNUSED
            : InviteCodeModel::STATUS_DISABLED;
        $code->status = $newStatus;
        $code->save();
        AdminLogService::record((int) $this->request->currentAdmin->id, 'toggle_invite_code', (string) $id);
        return json(['code' => 0, 'message' => '操作成功', 'data' => ['status' => $newStatus]]);
    }
}
