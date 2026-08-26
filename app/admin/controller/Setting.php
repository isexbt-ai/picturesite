<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\middleware\AdminAuth;
use app\common\model\Setting as SettingModel;
use app\common\service\AdminLogService;
use think\response\Json;

/**
 * 后台系统设置
 */
class Setting extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 读取全部设置
     */
    public function index(): Json
    {
        $list = SettingModel::field('key,value,remark')->select();
        $data = [];
        foreach ($list as $row) {
            $data[$row->key] = $row->value;
        }
        // 合并默认值
        $defaults = [
            'site_name'          => '图站',
            'comment_enabled'    => '1',
            'comment_auto_approve' => '1',
        ];
        $data = array_merge($defaults, $data);
        return json(['code' => 0, 'message' => 'ok', 'data' => $data]);
    }

    /**
     * 保存设置
     */
    public function save(): Json
    {
        $data = $this->request->post();
        foreach ($data as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            SettingModel::setValue($key, (string) $value);
        }
        AdminLogService::record((int) $this->request->currentAdmin->id, 'save_settings', '');
        return json(['code' => 0, 'message' => '保存成功', 'data' => null]);
    }
}
