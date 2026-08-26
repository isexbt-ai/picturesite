<?php

// +----------------------------------------------------------------------
// | Cloudflare R2 配置
// +----------------------------------------------------------------------

return [
    // R2 账户 ID（endpoint 域名前缀）
    'account_id' => env('R2_ACCOUNT_ID', ''),
    // S3 API Token（R2 专属凭证）
    'access_key' => env('R2_ACCESS_KEY', ''),
    'secret_key' => env('R2_SECRET_KEY', ''),
    // 桶名
    'bucket'     => env('R2_BUCKET', ''),
    // 桶绑定的自定义域名（走 Cloudflare CDN），如 https://img.example.com
    'cdn_domain' => env('R2_CDN_DOMAIN', ''),
];
