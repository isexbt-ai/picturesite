<template>
  <div class="page" v-loading="loading">
    <div class="page-header">
      <h2 class="page-title">{{ isEdit ? '编辑内容' : '新建内容' }}</h2>
      <el-button @click="router.back()">返回</el-button>
    </div>
    <el-card style="max-width: 860px">
      <el-form label-width="90px">
        <el-form-item label="标题" required>
          <el-input v-model="form.title" maxlength="150" placeholder="内容标题" />
        </el-form-item>
        <el-form-item label="副标题">
          <el-input v-model="form.subtitle" maxlength="255" placeholder="可选" />
        </el-form-item>
        <el-form-item label="类型" required>
          <el-radio-group v-model="form.type">
            <el-radio-button value="album">图集</el-radio-button>
            <el-radio-button value="single">单图</el-radio-button>
            <el-radio-button value="video">视频</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="等级">
          <el-select v-model="form.level" style="width: 180px">
            <el-option label="L0 注册可见" :value="0" />
            <el-option label="L1 V1 可见" :value="1" />
            <el-option label="L2 V2 可见" :value="2" />
            <el-option label="L3 V3 可见" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item label="分类">
          <el-select v-model="form.category_id" style="width: 220px" clearable>
            <el-option v-for="c in categories" :key="c.id" :label="c.name" :value="c.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="form.status" style="width: 180px">
            <el-option label="草稿" :value="0" />
            <el-option label="发布" :value="1" />
            <el-option label="下架" :value="2" />
          </el-select>
        </el-form-item>
        <el-form-item label="标签">
          <el-select v-model="tagNames" multiple filterable allow-create default-first-option style="width: 100%"
                     placeholder="输入标签名回车创建">
            <el-option v-for="t in tagOptions" :key="t.id" :label="t.name" :value="t.name" />
          </el-select>
        </el-form-item>

        <el-form-item label="封面">
          <div class="cover-wrap">
            <el-image v-if="coverPreview" :src="coverPreview" fit="cover" class="cover-preview" />
            <el-button type="primary" plain @click="pickCover">{{ coverPreview ? '更换封面' : '上传封面' }}</el-button>
          </div>
        </el-form-item>

        <!-- 图片内容：图集/单图 -->
        <template v-if="form.type !== 'video'">
          <el-form-item label="图片">
            <div class="flex items-center gap-3 flex-wrap">
              <el-button type="success" plain @click="pickImages">
                <el-icon class="mr-1"><upload-filled /></el-icon>上传图片
              </el-button>
              <span v-if="form.images.length" class="text-sm text-[#6e6e73]">
                已添加 {{ form.images.length }} 张
              </span>
              <el-button
                v-if="form.images.length"
                size="small"
                type="danger"
                plain
                @click="confirmClearImages">
                清空全部
              </el-button>
            </div>
            <input ref="imageInput" type="file" multiple accept="image/*" hidden @change="onImageFilesPicked">
            <div v-if="uploadStats.total > 0" class="upload-status">
              <el-progress
                :percentage="totalPct"
                :stroke-width="14"
                :show-text="true"
                :status="uploadStats.failed > 0 ? 'warning' : ''" />
              <p class="upload-meta">
                共 {{ uploadStats.total }} 张 · 已完成 {{ uploadStats.done }} · 失败 {{ uploadStats.failed }}
              </p>
              <el-button v-if="failedFiles.length" size="small" type="warning" plain @click="retryFailed">
                重试失败 ({{ failedFiles.length }})
              </el-button>
            </div>
            <div v-if="form.images.length" class="image-grid-wrap">
              <div
                v-for="(img, idx) in form.images"
                :key="img.path"
                class="image-grid-item">
                <el-image
                  :src="mediaUrl(img.thumb_path || img.path)"
                  fit="cover"
                  lazy
                  class="image-grid-thumb"
                  :preview-src-list="[mediaUrl(img.path)]"
                  :initial-index="0"
                  hide-on-click-modal />
                <div class="image-grid-overlay">
                  <span class="image-grid-idx">#{{ idx + 1 }}</span>
                  <el-button
                    class="image-grid-del"
                    type="danger"
                    circle
                    size="small"
                    :title="`删除第 ${idx + 1} 张`"
                    @click="removeImage(idx)">
                    <el-icon><close /></el-icon>
                  </el-button>
                </div>
              </div>
            </div>
          </el-form-item>
        </template>

        <!-- 视频内容 -->
        <template v-else>
          <el-form-item label="视频文件">
            <el-button type="success" plain @click="pickVideoFile">上传 MP4</el-button>
            <span v-if="form.video.path" class="video-info">已上传：{{ form.video.path }}</span>
          </el-form-item>
          <el-form-item label="视频封面">
            <el-button plain @click="pickVideoPoster">上传封面图</el-button>
          </el-form-item>
          <el-form-item label="时长(秒)">
            <el-input-number v-model="form.video.duration" :min="0" />
          </el-form-item>
        </template>

        <el-form-item>
          <el-button
            type="primary"
            :loading="saving"
            :disabled="isUploading"
            @click="handleSave">
            {{ isUploading ? '上传中…' : '保存内容' }}
          </el-button>
          <span v-if="isUploading" class="text-xs text-[#86868b] ml-3">
            上传未完成，请等待全部图片入库后再保存
          </span>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import { UploadFilled, Close } from '@element-plus/icons-vue'
import type { Category, ImageItem, Tag } from '../api'
import {
  checkImageHashes,
  createDraftAlbum,
  getAlbum,
  getCategories,
  getTags,
  saveAlbum,
  uploadImage,
  uploadVideo,
} from '../api'
import { mediaUrl } from '../utils/media'
import { sha256, uuid } from '../utils/hash'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id)

const loading = ref(false)
const saving = ref(false)
const categories = ref<Category[]>([])
const tagOptions = ref<Tag[]>([])
const tagNames = ref<string[]>([])
const coverPreview = ref('')
const imageInput = ref<HTMLInputElement | null>(null)

// 上传并发池：6 路同时进行；与 PHP-FPM worker 数（20+）留足余量
const UPLOAD_MAX_CONCURRENCY = 6
interface QueuedItem {
  file: File
  sha256: string
  clientUuid: string
}
const uploadPool = reactive({ active: 0, queue: [] as QueuedItem[] })
const uploadStats = reactive({ total: 0, done: 0, failed: 0 })
const failedFiles = ref<QueuedItem[]>([])
const totalPct = computed(() =>
  uploadStats.total > 0 ? Math.round((uploadStats.done / uploadStats.total) * 100) : 0,
)
/** 草稿 album 创建单飞：避免并发 onImageFilesPicked 各调一次 createDraftAlbum */
let draftCreating: Promise<number> | null = null
/** 上传中（含预检/draft 创建/并发池任务）：用于禁用「保存内容」按钮，避免增量同步删除未完成项 */
const isUploading = computed(
  () => uploadPool.active > 0 || uploadPool.queue.length > 0 || draftCreating !== null,
)

const form = reactive({
  id: 0,
  title: '',
  subtitle: '',
  type: 'album' as 'album' | 'single' | 'video',
  level: 0,
  category_id: 0,
  status: 1,
  cover: '',
  cover_thumb: '',
  images: [] as ImageItem[],
  video: { path: '', poster: '', duration: 0, width: 0, height: 0, size: 0 },
})

function pickImages(): void {
  imageInput.value?.click()
}

/**
 * 确保有可用的草稿 album_id；首次上传时调用，并发场景单飞
 */
function ensureDraftAlbum(): Promise<number> {
  if (form.id > 0) {
    return Promise.resolve(form.id)
  }
  if (draftCreating) {
    return draftCreating
  }
  draftCreating = createDraftAlbum(form.type)
    .then((res) => {
      form.id = res.data.id
      return form.id
    })
    .catch((err) => {
      draftCreating = null
      throw err
    })
  return draftCreating
}

async function onImageFilesPicked(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const files = input.files ? Array.from(input.files) : []
  if (!files.length) return

  // 视频类型不允许走图集上传
  if (form.type === 'video') {
    ElMessage.warning('视频内容请用上方「上传 MP4」')
    input.value = ''
    return
  }

  try {
    await ensureDraftAlbum()
  } catch {
    ElMessage.error('初始化草稿失败，请重试')
    input.value = ''
    return
  }

  uploadStats.total += files.length

  // 1) 并行算所有文件 SHA256（Web Crypto，纯本地）
  const items: QueuedItem[] = await Promise.all(
    files.map(async (file) => ({
      file,
      sha256: await sha256(file),
      clientUuid: uuid(),
    })),
  )

  // 2) 批量预检：DB 已有的直接复用，不上传
  const hashes = items.map((i) => i.sha256)
  try {
    const { data } = await checkImageHashes(hashes)
    const hitMap = data.hashes || {}
    for (const item of items) {
      const hit = hitMap[item.sha256]
      if (hit && hit.path) {
        pushImageIfNew({
          id: hit.id,
          path: hit.path,
          thumb_path: hit.thumb_path ?? '',
          width: hit.width ?? 0,
          height: hit.height ?? 0,
          size: hit.size ?? 0,
          sort: form.images.length + 1,
          sha256: hit.sha256 ?? item.sha256,
          client_uuid: hit.client_uuid ?? item.clientUuid,
        })
        uploadStats.done++
      } else {
        uploadPool.queue.push(item)
      }
    }
  } catch {
    // 预检失败时全部走上传
    uploadPool.queue.push(...items)
  }

  pump()
  input.value = ''
}

/**
 * 推入 form.images（按 path 去重，避免前端重复导致 DB 重复入库）
 */
function pushImageIfNew(img: ImageItem): void {
  if (form.images.some((x) => x.path === img.path)) return
  form.images.push(img)
}

async function uploadOne(item: QueuedItem): Promise<void> {
  try {
    const res = await uploadImage(item.file, {
      sha256: item.sha256,
      client_uuid: item.clientUuid,
      album_id: form.id,
      skipErrorToast: true,
    })
    pushImageIfNew({
      id: res.data.id,
      path: res.data.path,
      thumb_path: res.data.thumb_path ?? '',
      width: res.data.width ?? 0,
      height: res.data.height ?? 0,
      size: res.data.size ?? 0,
      sort: form.images.length + 1,
      sha256: res.data.sha256 ?? item.sha256,
      client_uuid: res.data.client_uuid ?? item.clientUuid,
    })
    uploadStats.done++
  } catch {
    uploadStats.failed++
    failedFiles.value.push(item)
  } finally {
    uploadPool.active--
    pump()
  }
}

function pump(): void {
  while (uploadPool.active < UPLOAD_MAX_CONCURRENCY && uploadPool.queue.length > 0) {
    const next = uploadPool.queue.shift()
    if (!next) break
    uploadPool.active++
    void uploadOne(next)
  }
}

function retryFailed(): void {
  if (!failedFiles.value.length) return
  failedFiles.value.forEach((f) => {
    uploadStats.total++
    uploadPool.queue.push(f)
  })
  failedFiles.value = []
  pump()
}

function clearImages(): void {
  form.images = []
}

/**
 * 删除单张图片：仅在前端 form.images 中移除，未保存不入库；保存后由 syncImages 增量 DELETE 物理删除
 */
function removeImage(idx: number): void {
  if (idx < 0 || idx >= form.images.length) return
  form.images.splice(idx, 1)
  ElMessage.success(`已移除第 ${idx + 1} 张`)
}

/**
 * 清空全部二次确认：避免误操作丢失大量图片
 */
async function confirmClearImages(): Promise<void> {
  if (!form.images.length) return
  try {
    await ElMessageBox.confirm(
      `确定清空全部 ${form.images.length} 张图片？保存前可撤销。`,
      '提示',
      { type: 'warning', confirmButtonText: '清空', cancelButtonText: '取消' },
    )
  } catch {
    return
  }
  clearImages()
  ElMessage.success('已清空全部图片')
}

async function handleUploadCover(file: File): Promise<void> {
  const { data } = await uploadImage(file)
  form.cover = data.path
  form.cover_thumb = data.thumb_path ?? ''
  coverPreview.value = mediaUrl(data.path)
  ElMessage.success('封面上传成功')
}

function pickCover(): void {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.onchange = () => {
    const file = input.files?.[0]
    if (file) void handleUploadCover(file)
  }
  input.click()
}

async function handleUploadVideoFile(file: File): Promise<void> {
  const { data } = await uploadVideo(file)
  form.video.path = data.path
  form.video.size = data.size
  ElMessage.success('视频上传成功')
}

function pickVideoFile(): void {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'video/mp4'
  input.onchange = () => {
    const file = input.files?.[0]
    if (file) void handleUploadVideoFile(file)
  }
  input.click()
}

async function handleUploadPoster(file: File): Promise<void> {
  const { data } = await uploadImage(file)
  form.video.poster = data.path
  ElMessage.success('视频封面上传成功')
}

function pickVideoPoster(): void {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.onchange = () => {
    const file = input.files?.[0]
    if (file) void handleUploadPoster(file)
  }
  input.click()
}

async function loadOptions(): Promise<void> {
  const [cats, tags] = await Promise.all([getCategories(), getTags()])
  categories.value = cats.data
  tagOptions.value = tags.data
}

async function loadAlbum(): Promise<void> {
  const id = Number(route.params.id)
  const { data } = await getAlbum(id)
  form.id = data.id
  form.title = data.title
  form.subtitle = data.subtitle
  form.type = data.type
  form.level = data.level
  form.category_id = data.category_id
  form.status = data.status
  form.cover = data.cover
  form.cover_thumb = data.cover_thumb ?? ''
  coverPreview.value = mediaUrl(data.cover)
  form.images = (data.images || []).map((img) => ({ ...img }))
  if (data.video) form.video = { ...data.video }
  tagNames.value = (data.tags || []).map((t) => t.name)
}

async function handleSave(): Promise<void> {
  if (!form.title) {
    ElMessage.warning('请输入标题')
    return
  }
  saving.value = true
  try {
    const payload: Record<string, unknown> = {
      id: form.id || undefined,
      title: form.title,
      subtitle: form.subtitle,
      type: form.type,
      level: form.level,
      category_id: form.category_id,
      status: form.status,
      cover: form.cover,
      cover_thumb: form.cover_thumb,
      tags: tagNames.value,
    }
    if (form.type === 'video') {
      payload.video = form.video
    } else {
      // 保留 id/sha256/client_uuid 让后端做增量 UPDATE；新上传的项也有 client_uuid 可走增量模式
      payload.images = form.images.map((img, i) => ({
        id: img.id,
        path: img.path,
        thumb_path: img.thumb_path,
        sha256: img.sha256,
        client_uuid: img.client_uuid,
        width: img.width,
        height: img.height,
        size: img.size,
        sort: i + 1,
      }))
    }
    const { data } = await saveAlbum(payload)
    // 新建场景下后端返回真实 id，前端同步（草稿场景下与 form.id 一致）
    if (data.id) form.id = data.id
    ElMessage.success('保存成功')
    router.push('/contents')
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await loadOptions()
    if (isEdit.value) {
      await loadAlbum()
    }
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.cover-wrap {
  display: flex;
  align-items: center;
  gap: 12px;
}
.cover-preview {
  width: 96px;
  height: 128px;
  border-radius: 6px;
}
.upload-status {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 12px;
  max-width: 520px;
}
.upload-meta {
  font-size: 13px;
  color: #86868b;
  margin: 0;
}
.video-info {
  margin-left: 12px;
  color: #909399;
  font-size: 13px;
  word-break: break-all;
}
.image-grid-wrap {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 8px;
  margin-top: 12px;
  max-height: 480px;
  overflow-y: auto;
  padding: 4px;
  border: 1px solid #ebeef5;
  border-radius: 4px;
  background: #fafafa;
}
.image-grid-item {
  position: relative;
  aspect-ratio: 1 / 1;
  border-radius: 4px;
  overflow: hidden;
  background: #f0f0f0;
  cursor: zoom-in;
}
.image-grid-thumb {
  width: 100%;
  height: 100%;
  display: block;
}
.image-grid-thumb :deep(img) {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.image-grid-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 4px;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.45) 0%, rgba(0, 0, 0, 0) 40%);
  opacity: 0;
  transition: opacity 0.15s ease;
  pointer-events: none;
}
.image-grid-item:hover .image-grid-overlay {
  opacity: 1;
}
.image-grid-idx {
  font-size: 11px;
  color: #fff;
  background: rgba(0, 0, 0, 0.55);
  padding: 2px 6px;
  border-radius: 8px;
  line-height: 1.4;
}
.image-grid-del {
  pointer-events: auto;
  transform: scale(0.9);
}
.image-grid-item:hover .image-grid-del {
  transform: scale(1);
}
</style>
