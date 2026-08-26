<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\middleware\AdminAuth;
use app\common\model\Card as CardModel;
use app\common\service\AdminLogService;
use think\response\Json;
use think\response\Response;

/**
 * 后台卡密管理
 */
class Card extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 卡密列表（按批次 + 状态过滤）
     */
    public function index(): Json
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $size = min(200, max(1, (int) $this->request->get('size', 20)));
        $query = CardModel::order('id desc');

        if ($this->request->get('batch_id', '') !== '') {
            $query->where('batch_id', (int) $this->request->get('batch_id'));
        }
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
     * 禁用/启用卡密
     */
    public function toggle(int $id): Json
    {
        $card = CardModel::find($id);
        if (!$card) {
            throw new BizException('卡密不存在', 2011);
        }
        $newStatus = (int) $card->status === CardModel::STATUS_DISABLED
            ? CardModel::STATUS_UNUSED
            : CardModel::STATUS_DISABLED;
        $card->status = $newStatus;
        $card->save();
        AdminLogService::record((int) $this->request->currentAdmin->id, 'toggle_card', (string) $id);
        return json(['code' => 0, 'message' => '操作成功', 'data' => ['status' => $newStatus]]);
    }

    /**
     * 导出批次未使用卡密（纯文本，每行一个）
     */
    public function export(int $batchId): Response
    {
        $codes = CardModel::where('batch_id', $batchId)
            ->where('status', CardModel::STATUS_UNUSED)
            ->order('id asc')
            ->column('code');
        $content = implode("\n", $codes) . "\n";
        AdminLogService::record((int) $this->request->currentAdmin->id, 'export_cards', (string) $batchId);
        return response($content, 200, ['Content-Type' => 'text/plain; charset=utf-8'])
            ->header('Content-Disposition', 'attachment; filename="cards_' . $batchId . '.txt"');
    }
}
