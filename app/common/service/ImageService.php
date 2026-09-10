<?php
declare(strict_types=1);

namespace app\common\service;

/**
 * 图片处理服务（GD 实现）：等比缩放生成 WebP 中图
 * 服务器无 Imagick 时 GD 兜底，本机 GD 已启用。
 */
class ImageService
{
    /** 列表/中图宽度（详情中档尺寸 + 列表封面统一使用） */
    public const LIST_WIDTH = 800;

    /**
     * 处理图片：等比缩放生成 WebP，输出到指定目录
     *
     * @param string $src      源图本地路径
     * @param string $destDir  输出目录（必须已存在或可创建）
     * @param int    $quality  WebP 质量 0-100
     * @return array{webp:string} 生成的 WebP 本地路径
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
        $webpPath = $base . '_list.webp';

        $list = self::scaleTo($image, self::LIST_WIDTH, $srcW, $srcH);
        imagewebp($list, $webpPath, $quality);

        imagedestroy($image);
        imagedestroy($list);

        return ['webp' => $webpPath];
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