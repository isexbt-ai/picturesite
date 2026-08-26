<?php
declare(strict_types=1);

namespace app\common\service;

use app\common\exception\BizException;
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

    /**
     * 上传图片：原图 + 缩略图 + WebP 三份写入存储
     *
     * @return array{path:string, thumb_path:string, webp_path:string, width:int, height:int, size:int}
     */
    public static function uploadImage(UploadedFile $file, string $prefix = 'images'): array
    {
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
        $dim = @getimagesize($src);
        $width = $dim[0] ?? 0;
        $height = $dim[1] ?? 0;

        $outDir = runtime_path() . 'tmp';
        $gen = ImageService::process($src, $outDir);

        $base = $prefix . '/' . date('Ym') . '/' . uniqid('', true);
        $origKey = $base . '.' . ($file->extension() ?: 'jpg');
        $thumbKey = $base . '_thumb.jpg';
        $webpKey = $base . '_list.webp';

        try {
            StorageService::put($origKey, $src);
            StorageService::put($thumbKey, $gen['thumb']);
            StorageService::put($webpKey, $gen['webp']);
        } finally {
            @unlink($src);
            @unlink($gen['thumb']);
            @unlink($gen['webp']);
        }

        return [
            'path'       => $origKey,
            'thumb_path' => $thumbKey,
            'webp_path'  => $webpKey,
            'width'      => (int) $width,
            'height'     => (int) $height,
            'size'       => $size,
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
