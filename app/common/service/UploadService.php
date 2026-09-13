<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BizException;
use app\common\model\Image as ImageModel;
use think\file\UploadedFile;

/**
 * 上传服务：图片（GD 多尺寸 + WebP）与视频（MP4）上传到存储
 */
class UploadService
{
    /** 图片最大 10MB */
    public const MAX_IMAGE = 10 * 1024 * 1024;
    /** 视频最大 500MB */
    public const MAX_VIDEO = 500 * 1024 * 1024;
    /** 预检 hash 数量上限（防滥用） */
    public const PRECHECK_MAX = 500;

    /**
     * 上传图片：原图 + 缩略图(jpg)两份写入存储
     * 按 SHA256 自动去重：同一文件二次上传直接复用 key，跳过 process 与 put
     *
     * @param string        $prefix       已废弃（路径统一为 images/by-hash/{xx}/{hash}.{ext}），保留仅为兼容调用方
     * @param string|null   $clientSha256 客户端预计算的 SHA256（命中则跳过服务端 hash_file，省一次读盘）
     * @param string|null   $clientUuid   前端为本次上传生成的稳定 UUID，由控制器写入 images 行用于增量同步
     * @return array{path:string, thumb_path:string, width:int, height:int, size:int, sha256:string, client_uuid:string}
     */
    public static function uploadImage(
        UploadedFile $file,
        string $prefix = 'images',
        ?string $clientSha256 = null,
        ?string $clientUuid = null,
    ): array {
        // 先校验 mime 与体积，避免大文件落盘后再拒绝
        $mime = (string) $file->getMime();
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new BizException('仅支持 JPG/PNG/WebP/GIF 图片', 1301);
        }
        if ($file->getSize() > self::MAX_IMAGE) {
            throw new BizException('图片不能超过 10MB', 1302);
        }

        $saved = self::saveTemp($file, 'up_');
        $src = $saved;
        $size = (int) filesize($src);

        // 从文件内容读取宽高 + 真实类型；扩展名必须按内容推断，
        // 不能用 $file->extension()（依赖客户端原始文件名，会因 .jpg/.jpeg 不一致导致 SHA256 去重失效）
        $dim = @getimagesize($src);
        if ($dim === false) {
            @unlink($src);
            throw new BizException('无法识别的图片文件', 1301);
        }
        $width = (int) $dim[0];
        $height = (int) $dim[1];
        $imageType = (int) $dim[2];
        $ext = match ($imageType) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_GIF  => 'gif',
            IMAGETYPE_WEBP => 'webp',
            default        => null,
        };
        if ($ext === null) {
            @unlink($src);
            throw new BizException('仅支持 JPG/PNG/WebP/GIF 图片', 1301);
        }

        // 优先用客户端预计算的 SHA256（避免二次读盘），缺失或格式非法时回退服务端 hash_file
        if (is_string($clientSha256) && preg_match('/^[a-f0-9]{64}$/', $clientSha256) === 1) {
            $hash = $clientSha256;
        } else {
            $hash = hash_file('sha256', $src);
        }
        $hashPrefix = substr($hash, 0, 2);
        $origKey = "images/by-hash/{$hashPrefix}/{$hash}.{$ext}";
        $thumbKey = "images/by-hash/{$hashPrefix}/{$hash}_thumb.jpg";

        if (StorageService::exists($origKey)) {
            @unlink($src);
            return self::result($origKey, $thumbKey, $width, $height, $size, $hash, $clientUuid);
        }

        $outDir = runtime_path() . 'tmp';
        $gen = ImageService::process($src, $outDir);

        try {
            StorageService::put($origKey, $src);
            StorageService::put($thumbKey, $gen['thumb']);
        } finally {
            @unlink($src);
            @unlink($gen['thumb']);
        }

        return self::result($origKey, $thumbKey, $width, $height, $size, $hash, $clientUuid);
    }

    /**
     * 批量预检：按 SHA256 查 images 表是否已存在（任何 album，含未归属）
     * 返回 map[hash => image row]，命中后前端可跳过上传直接复用 path/thumb_path
     *
     * @param string[] $hashes 64 位小写 hex 列表
     * @return array<string, array{id:int, path:string, thumb_path:string, width:int, height:int, size:int, sha256:string, client_uuid:string, album_id:int}>
     */
    public static function preCheck(array $hashes): array
    {
        if (empty($hashes)) {
            return [];
        }
        $hashes = array_values(array_unique(array_filter(
            $hashes,
            static fn($h) => is_string($h) && preg_match('/^[a-f0-9]{64}$/', $h) === 1,
        )));
        if (empty($hashes)) {
            return [];
        }
        if (count($hashes) > self::PRECHECK_MAX) {
            $hashes = array_slice($hashes, 0, self::PRECHECK_MAX);
        }

        $rows = ImageModel::where('sha256', 'in', $hashes)->select();
        $map = [];
        foreach ($rows as $row) {
            $hash = (string) ($row->sha256 ?? '');
            if ($hash === '') {
                continue;
            }
            $map[$hash] = [
                'id'          => (int) $row->id,
                'path'        => (string) $row->path,
                'thumb_path'  => (string) ($row->thumb_path ?? ''),
                'width'       => (int) ($row->width ?? 0),
                'height'      => (int) ($row->height ?? 0),
                'size'        => (int) ($row->size ?? 0),
                'sha256'      => $hash,
                'client_uuid' => (string) ($row->client_uuid ?? ''),
                'album_id'    => (int) $row->album_id,
            ];
        }
        return $map;
    }

    /**
     * 构造上传结果数组
     *
     * @return array{path:string, thumb_path:string, width:int, height:int, size:int, sha256:string, client_uuid:string}
     */
    private static function result(
        string $origKey,
        string $thumbKey,
        int $width,
        int $height,
        int $size,
        string $hash,
        ?string $clientUuid,
    ): array {
        return [
            'path'        => $origKey,
            'thumb_path'  => $thumbKey,
            'width'       => $width,
            'height'      => $height,
            'size'        => $size,
            'sha256'      => $hash,
            'client_uuid' => (string) ($clientUuid ?? ''),
        ];
    }

    /**
     * 上传视频：仅 MP4（H.264 + AAC），直传存储
     *
     * @return array{path:string, size:int}
     */
    public static function uploadVideo(UploadedFile $file, string $prefix = 'videos'): array
    {
        $ext = strtolower((string) $file->extension());
        if ($ext !== 'mp4') {
            throw new BizException('仅支持 MP4 视频（H.264 + AAC）', 1311);
        }
        if ($file->getSize() > self::MAX_VIDEO) {
            throw new BizException('视频不能超过 500MB', 1312);
        }

        $tmp = runtime_path() . 'tmp';
        if (!is_dir($tmp)) {
            mkdir($tmp, 0755, true);
        }
        $tmpFile = $tmp . DIRECTORY_SEPARATOR . 'vid_' . uniqid('', true) . '.mp4';
        $file->move($tmp, basename($tmpFile));
        if (!is_file($tmpFile)) {
            throw new BizException('视频保存失败', 1313);
        }
        $size = (int) filesize($tmpFile);

        $key = $prefix . '/' . date('Ym') . '/' . uniqid('', true) . '.mp4';
        try {
            StorageService::put($key, $tmpFile);
        } finally {
            @unlink($tmpFile);
        }

        return ['path' => $key, 'size' => $size];
    }

    /**
     * 保存上传文件到 runtime/tmp 并返回绝对路径
     */
    private static function saveTemp(UploadedFile $file, string $prefix): string
    {
        $tmp = runtime_path() . 'tmp';
        if (!is_dir($tmp)) {
            mkdir($tmp, 0755, true);
        }
        $name = $prefix . uniqid('', true) . '.' . $file->extension();
        $file->move($tmp, $name);
        $path = $tmp . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) {
            throw new BizException('文件保存失败', 1303);
        }
        return $path;
    }
}
