/**
 * Web Crypto 工具：客户端算 SHA256 与生成 UUID，避免上传后再被服务端去重浪费带宽。
 *
 * - SHA256：MDN 推荐 crypto.subtle.digest，浏览器并发安全。
 * - UUID：crypto.randomUUID 是浏览器原生 API，主流浏览器均支持。
 *
 * 浏览器内存限制约 2GB；单张图 10MB 上限内 arrayBuffer 完全够用，无需流式分片。
 */

/**
 * 计算文件 SHA256（小写 hex）
 */
export async function sha256(file: File): Promise<string> {
  const buf = await file.arrayBuffer()
  const digest = await crypto.subtle.digest('SHA-256', buf)
  const bytes = new Uint8Array(digest)
  let out = ''
  for (let i = 0; i < bytes.length; i++) {
    out += bytes[i].toString(16).padStart(2, '0')
  }
  return out
}

/** 生成 v4 UUID，前端为每次上传打唯一标识，用于后端增量同步 sort 与删除 */
export function uuid(): string {
  return crypto.randomUUID()
}
