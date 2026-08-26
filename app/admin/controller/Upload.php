<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\middleware\AdminAuth;
use app\common\service\AdminLogService;
use app\common\service\UploadService;
use think\response\Json;

/**
 * 后台上传接口：图片（GD 多尺寸 + WebP）/ 视频（MP4）
 */
class Upload extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 上传图片
     */
    public function image(): Json
    {
        $file = $this->request->file('file');
        if (!$file) {
            return json(['code' => 1701, 'message' => '请选择图片文件', 'data' => null]);
        }
        $result = UploadService::uploadImage($file);
        AdminLogService::record((int) $this->request->currentAdmin->id, 'upload_image', $result['path']);
        return json(['code' => 0, 'message' => '上传成功', 'data' => $result]);
    }

    /**
     * 上传视频（仅 MP4）
     */
    public function video(): Json
    {
        $file = $this->request->file('file');
        if (!$file) {
            return json(['code' => 1702, 'message' => '请选择视频文件', 'data' => null]);
        }
        $result = UploadService::uploadVideo($file);
        AdminLogService::record((int) $this->request->currentAdmin->id, 'upload_video', $result['path']);
        return json(['code' => 0, 'message' => '上传成功', 'data' => $result]);
    }
}
