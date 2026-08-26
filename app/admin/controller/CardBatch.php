<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\middleware\AdminAuth;
use app\common\model\CardBatch as CardBatchModel;
use app\common\service\AdminLogService;
use app\common\service\CardService;
use think\response\Json;

/**
 * 后台卡密批次管理
 */
class CardBatch extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 批次列表
     */
    public function index(): Json
    {
        $list = CardBatchModel::order('id desc')->select();
        // 附带已用/未用统计
        $data = $list->map(function (CardBatchModel $b): array {
            $used = \app\common\model\Card::where('batch_id', (int) $b->id)
                ->where('status', \app\common\model\Card::STATUS_USED)->count();
            return array_merge($b->toArray(), ['used_count' => $used]);
        });
        return json(['code' => 0, 'message' => 'ok', 'data' => $data]);
    }

    /**
     * 创建批次并生成卡密
     */
    public function save(): Json
    {
        $data = $this->request->post();
        $this->validate($data, [
            'name'  => 'require|max:100',
            'level' => 'require|number',
            'days'  => 'require|number',
            'total' => 'require|number',
        ]);

        $batch = CardService::createBatch(
            (string) $data['name'],
            (int) $data['level'],
            (int) $data['days'],
            (int) $data['total']
        );
        AdminLogService::record((int) $this->request->currentAdmin->id, 'create_card_batch', (string) $batch->id);
        return json(['code' => 0, 'message' => '批次创建成功', 'data' => ['id' => (int) $batch->id]]);
    }

    /**
     * 批次详情（含卡密统计）
     */
    public function detail(int $id): Json
    {
        $batch = CardBatchModel::find($id);
        if (!$batch) {
            throw new BizException('批次不存在', 2001);
        }
        $stats = [
            'total'  => (int) \app\common\model\Card::where('batch_id', $id)->count(),
            'used'   => (int) \app\common\model\Card::where('batch_id', $id)->where('status', \app\common\model\Card::STATUS_USED)->count(),
            'unused' => (int) \app\common\model\Card::where('batch_id', $id)->where('status', \app\common\model\Card::STATUS_UNUSED)->count(),
            'disabled' => (int) \app\common\model\Card::where('batch_id', $id)->where('status', \app\common\model\Card::STATUS_DISABLED)->count(),
        ];
        return json(['code' => 0, 'message' => 'ok', 'data' => ['batch' => $batch, 'stats' => $stats]]);
    }
}
