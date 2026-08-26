<?php
declare(strict_types=1);

namespace app\common\service;

use think\facade\Config;
use think\facade\Log;

/**
 * 存储服务：本地文件系统 / Cloudflare R2 双驱动
 * 通过 config/storage.php 的 default 切换；key 一律由服务端生成，杜绝路径穿越。
 */
class StorageService
{
    /**
     * 上传文件到存储，返回存储 key（相对路径，如 media/xxx.jpg）
     */
    public static function put(string $key, string $localFile): string
    {
        self::assertSafeKey($key);
        if (self::isR2()) {
            return self::putR2($key, $localFile);
        }
        return self::putLocal($key, $localFile);
    }

    /**
     * 生成文件访问 URL
     */
    public static function url(string $key): string
    {
        if (self::isR2()) {
            return rtrim((string) Config::get('r2.cdn_domain', ''), '/') . '/' . ltrim($key, '/');
        }
        return '/storage/' . ltrim($key, '/');
    }

    /**
     * 删除存储文件
     */
    public static function delete(string $key): void
    {
        self::assertSafeKey($key);
        if (self::isR2()) {
            self::deleteR2($key);
            return;
        }
        self::deleteLocal($key);
    }

    /**
     * 当前是否使用 R2 驱动
     */
    public static function isR2(): bool
    {
        return Config::get('storage.default', 'local') === 'r2';
    }

    /**
     * 本地驱动：拷贝文件到 public/storage
     */
    private static function putLocal(string $key, string $localFile): string
    {
        $dest = public_path() . 'storage' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $key);
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!copy($localFile, $dest)) {
            throw new \RuntimeException('本地存储写入失败: ' . $key);
        }
        return $key;
    }

    /**
     * R2 驱动：通过 S3Client 上传
     */
    private static function putR2(string $key, string $localFile): string
    {
        $mime = mime_content_type($localFile) ?: 'application/octet-stream';
        self::client()->putObject([
            'Bucket'      => (string) Config::get('r2.bucket'),
            'Key'         => $key,
            'SourceFile'  => $localFile,
            'ContentType' => $mime,
        ]);
        return $key;
    }

    /**
     * 删除本地文件
     */
    private static function deleteLocal(string $key): void
    {
        $file = public_path() . 'storage' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * 删除 R2 对象
     */
    private static function deleteR2(string $key): void
    {
        try {
            self::client()->deleteObject([
                'Bucket' => (string) Config::get('r2.bucket'),
                'Key'    => $key,
            ]);
        } catch (\Throwable $e) {
            Log::warning('R2 删除失败', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    /**
     * R2 S3Client（惰性单例）
     */
    private static function client(): \Aws\S3\S3Client
    {
        static $client = null;
        if ($client === null) {
            $client = new \Aws\S3\S3Client([
                'version'     => 'latest',
                'region'      => 'auto',
                'endpoint'    => 'https://' . Config::get('r2.account_id') . '.r2.cloudflarestorage.com',
                'credentials' => [
                    'key'    => (string) Config::get('r2.access_key'),
                    'secret' => (string) Config::get('r2.secret_key'),
                ],
            ]);
        }
        return $client;
    }

    /**
     * 校验 key 不包含路径穿越与特殊字符
     */
    private static function assertSafeKey(string $key): void
    {
        if ($key === '' || str_contains($key, '..') || str_contains($key, "\0")) {
            throw new \InvalidArgumentException('非法的存储 key');
        }
    }
}
