// 媒体 URL 拼接：后端存储返回的是 key（相对路径），
// 本地驱动前缀为 /storage，生产 R2 通过 VITE_MEDIA_BASE 配置 CDN 域名
const MEDIA_BASE = (import.meta.env.VITE_MEDIA_BASE as string | undefined) || '/storage'

/** 存储 key 转可访问 URL */
export function mediaUrl(key: string): string {
  if (!key) return ''
  if (/^https?:\/\//.test(key)) return key
  return `${MEDIA_BASE.replace(/\/$/, '')}/${key}`
}
