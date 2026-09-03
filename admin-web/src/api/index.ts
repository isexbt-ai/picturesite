import request from './request'
import type { ApiResponse } from './request'

// ---------- 类型定义 ----------
export interface Category {
  id: number
  name: string
  slug: string
  parent_id: number
  sort: number
  status: number
}

export interface Tag {
  id: number
  name: string
  slug: string
}

export interface AlbumItem {
  id: number
  title: string
  subtitle: string
  type: 'album' | 'single' | 'video'
  cover: string
  cover_thumb?: string
  cover_webp?: string
  level: number
  category_id: number
  status: number
  sort: number
  view_count: number
  like_count: number
  create_time: string
  category?: { name: string } | null
  images?: ImageItem[]
  video?: VideoItem | null
  tags?: Tag[]
}

export interface ImageItem {
  path: string
  thumb_path: string
  webp_path: string
  width: number
  height: number
  size: number
  sort: number
}

export interface VideoItem {
  path: string
  poster: string
  duration: number
  width: number
  height: number
  size: number
}

export interface CardBatch {
  id: number
  name: string
  level: number
  duration_days: number
  total: number
  used_count?: number
}

export interface CardItem {
  id: number
  batch_id: number
  code: string
  level: number
  duration_days: number
  status: number
  used_by: number | null
  used_at: string | null
}

export interface InviteCode {
  id: number
  code: string
  status: number
  used_by: number | null
  used_at: string | null
  expire_at: string | null
}

export interface UserItem {
  id: number
  username: string
  email: string | null
  vip_level: number
  vip_expire_at: string | null
  status: number
  create_time: string
}

export interface CommentItem {
  id: number
  album_id: number
  user_id: number
  content: string
  status: number
  create_time: string
  user?: { username: string }
  album?: { title: string }
}

export interface Stats {
  album_total: number
  album_type: { album: number; single: number; video: number }
  user_total: number
  vip_total: number
  view_total: number
  card_used: number
  card_unused: number
  invite_used: number
  invite_total: number
}

export interface Paged<T> {
  items: T[]
  total: number
  page: number
}

// ---------- 认证 ----------
export const login = (data: { username: string; password: string }): Promise<ApiResponse<{ token: string }>> =>
  request.post('/auth/login', data) as Promise<ApiResponse<{ token: string }>>

export const logout = (): Promise<ApiResponse<null>> =>
  request.post('/auth/logout') as Promise<ApiResponse<null>>

export const getMe = (): Promise<ApiResponse<{ id: number; username: string }>> =>
  request.get('/auth/me') as Promise<ApiResponse<{ id: number; username: string }>>

// ---------- 分类 ----------
export const getCategories = (): Promise<ApiResponse<Category[]>> =>
  request.get('/category/index') as Promise<ApiResponse<Category[]>>

export const saveCategory = (data: Partial<Category>): Promise<ApiResponse<{ id: number }>> =>
  request.post('/category/save', data) as Promise<ApiResponse<{ id: number }>>

export const deleteCategory = (id: number): Promise<ApiResponse<null>> =>
  request.post(`/category/delete/id/${id}`) as Promise<ApiResponse<null>>

// ---------- 标签 ----------
export const getTags = (): Promise<ApiResponse<Tag[]>> =>
  request.get('/tag/index') as Promise<ApiResponse<Tag[]>>

export const saveTag = (data: Partial<Tag>): Promise<ApiResponse<{ id: number }>> =>
  request.post('/tag/save', data) as Promise<ApiResponse<{ id: number }>>

export const deleteTag = (id: number): Promise<ApiResponse<null>> =>
  request.post(`/tag/delete/id/${id}`) as Promise<ApiResponse<null>>

// ---------- 内容 ----------
export const getAlbums = (params: Record<string, unknown>): Promise<ApiResponse<Paged<AlbumItem>>> =>
  request.get('/album/index', { params }) as Promise<ApiResponse<Paged<AlbumItem>>>

export const getAlbum = (id: number): Promise<ApiResponse<AlbumItem>> =>
  request.get(`/album/detail/id/${id}`) as Promise<ApiResponse<AlbumItem>>

export const saveAlbum = (data: Record<string, unknown>): Promise<ApiResponse<{ id: number }>> =>
  request.post('/album/save', data) as Promise<ApiResponse<{ id: number }>>

export const deleteAlbum = (id: number): Promise<ApiResponse<null>> =>
  request.post(`/album/delete/id/${id}`) as Promise<ApiResponse<null>>

export const uploadImage = (file: File): Promise<ApiResponse<ImageItem>> => {
  const form = new FormData()
  form.append('file', file)
  return request.post('/upload/image', form) as Promise<ApiResponse<ImageItem>>
}

export const uploadVideo = (file: File): Promise<ApiResponse<{ path: string; size: number }>> => {
  const form = new FormData()
  form.append('file', file)
  return request.post('/upload/video', form) as Promise<ApiResponse<{ path: string; size: number }>>
}

// ---------- 卡密批次 ----------
export const getCardBatches = (): Promise<ApiResponse<CardBatch[]>> =>
  request.get('/card-batch/index') as Promise<ApiResponse<CardBatch[]>>

export const saveCardBatch = (data: { name: string; level: number; days: number; total: number }): Promise<ApiResponse<{ id: number }>> =>
  request.post('/card-batch/save', data) as Promise<ApiResponse<{ id: number }>>

// ---------- 卡密 ----------
export const getCards = (params: Record<string, unknown>): Promise<ApiResponse<Paged<CardItem>>> =>
  request.get('/card/index', { params }) as Promise<ApiResponse<Paged<CardItem>>>

export const toggleCard = (id: number): Promise<ApiResponse<{ status: number }>> =>
  request.post(`/card/toggle/id/${id}`) as Promise<ApiResponse<{ status: number }>>

export const exportCards = (batchId: number): Promise<ApiResponse<null>> =>
  request.get(`/card/export/batch_id/${batchId}`) as Promise<ApiResponse<null>>

// ---------- VIP ----------
export const grantVip = (data: { username: string; level: number; days: number; remark?: string }): Promise<ApiResponse<{ new_level: number; vip_expire_at: string }>> =>
  request.post('/vip/grant', data) as Promise<ApiResponse<{ new_level: number; vip_expire_at: string }>>

// ---------- 用户 ----------
export const getUsers = (params: Record<string, unknown>): Promise<ApiResponse<Paged<UserItem>>> =>
  request.get('/user/index', { params }) as Promise<ApiResponse<Paged<UserItem>>>

// ---------- 邀请码 ----------
export const getInviteCodes = (params: Record<string, unknown>): Promise<ApiResponse<Paged<InviteCode>>> =>
  request.get('/invite-code/index', { params }) as Promise<ApiResponse<Paged<InviteCode>>>

export const generateInviteCodes = (count: number): Promise<ApiResponse<{ count: number }>> =>
  request.post('/invite-code/generate', { count }) as Promise<ApiResponse<{ count: number }>>

export const toggleInviteCode = (id: number): Promise<ApiResponse<{ status: number }>> =>
  request.post(`/invite-code/toggle/id/${id}`) as Promise<ApiResponse<{ status: number }>>

// ---------- 评论 ----------
export const getComments = (params: Record<string, unknown>): Promise<ApiResponse<Paged<CommentItem>>> =>
  request.get('/comment/index', { params }) as Promise<ApiResponse<Paged<CommentItem>>>

export const toggleComment = (id: number): Promise<ApiResponse<{ status: number }>> =>
  request.post(`/comment/toggle/id/${id}`) as Promise<ApiResponse<{ status: number }>>

export const deleteComment = (id: number): Promise<ApiResponse<null>> =>
  request.post(`/comment/delete/id/${id}`) as Promise<ApiResponse<null>>

// ---------- 设置 ----------
export const getSettings = (): Promise<ApiResponse<Record<string, string>>> =>
  request.get('/setting/index') as Promise<ApiResponse<Record<string, string>>>

export const saveSettings = (data: Record<string, string>): Promise<ApiResponse<null>> =>
  request.post('/setting/save', data) as Promise<ApiResponse<null>>

// ---------- 统计 ----------
export const getDashboard = (): Promise<ApiResponse<Stats>> =>
  request.get('/stats/dashboard') as Promise<ApiResponse<Stats>>
