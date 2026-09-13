<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\BaseController;
use app\common\exception\BizException;
use app\common\middleware\AdminAuth;
use app\common\model\Image as ImageModel;
use app\common\service\AdminLogService;
use app\common\service\UploadService;
use think\file\UploadedFile;
use think\response\Json;

/**
 * 后台上传接口：图片（GD 多尺寸 + WebP）/ 视频（MP4）
 * - image() 同时支持「媒体库上传」（带 client_uuid，落 images 行）和「封面/海报上传」（无 client_uuid，仅返回 key）
 * - check() 批量预检 SHA256 已存在的 image 行
 */
class Upload extends BaseController
{
    protected $middleware = [AdminAuth::class];

    /**
     * 上传图片
     *
     * 表单字段：
     *   file         必填，二进制文件
     *   sha256       可选，客户端预计算的 SHA256（命中则跳过服务端 hash_file）
     *   client_uuid  可选，封面/海报留空；媒体库上传必填，用于增量同步 sort 与删除
     *   album_id     可选，0=不绑定（封面场景）或当前草稿 id
     */
    public function image(): Json
    {
        $file = $this->request->file('file');
        if (!$file) {
            return json(['code' => 1701, 'message' => '请选择图片文件', 'data' => null]);
        }
        $clientUuid = $this->stringField('client_uuid');
        $clientSha256 = $this->stringField('sha256');
        $albumId = max(0, (int) $this->request->post('album_id', 0));

        $result = UploadService::uploadImage($file, 'images', $clientSha256, $clientUuid);

        // 媒体库上传（带 client_uuid）落 images 行；封面/海报场景不落
        $imageId = 0;
        if ($clientUuid !== '') {
            $imageId = (int) ImageModel::create([
                'album_id'    => $albumId,
                'path'        => $result['path'],
                'thumb_path'  => $result['thumb_path'],
                'sha256'      => $result['sha256'],
                'client_uuid' => $result['client_uuid'],
                'sort'        => 0,
                'width'       => $result['width'],
                'height'      => $result['height'],
                'size'        => $result['size'],
            ])->id;
        }

        $result['id'] = $imageId;
        $result['album_id'] = $albumId;

        AdminLogService::record((int) $this->request->currentAdmin->id, 'upload_image', $result['path']);
        return json(['code' => 0, 'message' => '上传成功', 'data' => $result]);
    }

    /**
     * 批量预检：客户端在上传前按 SHA256 查询已存在的 image 行
     * 命中即跳过实际上传，直接复用 path/thumb_path
     *
     * @return Json {code:0, data: {hashes: {hash: {id,path,thumb_path,...}|null}}}
     */
    public function check(): Json
    {
        $hashes = $this->request->post('hashes/a', []);
        $map = UploadService::preCheck(is_array($hashes) ? $hashes : []);
        return json(['code' => 0, 'message' => 'ok', 'data' => ['hashes' => $map]]);
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

    /**
     * 取字符串字段，空串视为 null
     */
    private function stringField(string $name): string
    {
        $val = $this->request->post($name, '');
        return is_string($val) ? trim($val) : '';
    }
}
