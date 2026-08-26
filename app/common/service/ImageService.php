<?php
declare(strict_types=1);

namespace app\common\service;

/**
 * 图片处理服务（GD 实现）：缩略图 + WebP
 * 服务器无 Imagick 时 GD 兜底，本机 GD 已启用。
 */
class ImageService
{
    /** 封面/缩略图宽度（列表页卡片用） */
    public const THUMB_WIDTH = 400;
    /** 列表图宽度（详情灯箱用） */
    public const LIST_WIDTH = 800;

    /**
     * 处理图片：等比缩放生成缩略图(jpg)与 WebP，输出到指定目录
     *
     * @param string $src      源图本地路径
     * @param string $destDir  输出目录（必须已存在或可创建）
     * @param int    $quality  WebP 质量 0-100
     * @return array{thumb:string, webp:string} 生成的缩略图与 WebP 本地路径
     */
    public static function process(string $src, string $destDir, int $quality = 80): array
    {
        $info = @getimagesize($src);
        if ($info === false) {
            throw new \InvalidArgumentException('无法识别的图片文件');
        }
        [$srcW, $srcH] = $info;
        $mime = $info['mime'];

        $image = self::readImage($src, $mime);
        if ($image === false) {
            throw new \InvalidArgumentException('不支持的图片格式: ' . $mime);
        }

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        $base = $destDir . DIRECTORY_SEPARATOR . uniqid('img_', true);
        $thumbPath = $base . '_thumb.jpg';
        $webpPath  = $base . '_list.webp';

        $thumb = self::scaleTo($image, self::THUMB_WIDTH, $srcW, $srcH);
        imagejpeg($thumb, $thumbPath, (int) round($quality * 1.1));

        $list = self::scaleTo($image, self::LIST_WIDTH, $srcW, $srcH);
        imagewebp($list, $webpPath, $quality);

        imagedestroy($image);
        imagedestroy($thumb);
        imagedestroy($list);

        return ['thumb' => $thumbPath, 'webp' => $webpPath];
    }

    /**
     * 等比缩放到不超过 maxWidth
     */
    private static function scaleTo(\GdImage $src, int $maxWidth, int $srcW, int $srcH): \GdImage
    {
        $scale = $srcW > $maxWidth ? $maxWidth / $srcW : 1.0;
        $newW = (int) max(1, round($srcW * $scale));
        $newH = (int) max(1, round($srcH * $scale));
        $dest = imagescale($src, $newW, $newH);
        if ($dest === false) {
            throw new \RuntimeException('图片缩放失败');
        }
        return $dest;
    }

    /**
     * 按 MIME 读取图像
     */
    private static function readImage(string $src, string $mime): \GdImage|false
    {
        return match ($mime) {
            'image/jpeg'       => imagecreatefromjpeg($src),
            'image/png'        => imagecreatefrompng($src),
            'image/webp'       => imagecreatefromwebp($src),
            'image/gif'        => imagecreatefromgif($src),
            default            => false,
        };
    }
}
