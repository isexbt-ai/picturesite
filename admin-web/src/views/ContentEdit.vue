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
            <div class="flex items-center gap-3">
              <el-button type="success" plain @click="pickImages">
                <el-icon class="mr-1"><upload-filled /></el-icon>上传图片
              </el-button>
              <input ref="imageInput" type="file" multiple accept="image/*" hidden @change="onImageFilesPicked">
            </div>
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
          </el-form-item>
          <el-form-item v-if="form.images.length" label="图片数量">
            <div class="flex items-center gap-3">
              <span class="text-sm text-[#6e6e73]">已添加 {{ form.images.length }} 张</span>
              <el-button size="small" type="danger" plain @click="clearImages">清空全部</el-button>
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
          <el-button type="primary" :loading="saving" @click="handleSave">保存内容</el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { UploadFilled } from '@element-plus/icons-vue'
import type { Category, ImageItem, Tag } from '../api'
import { getAlbum, getCategories, getTags, saveAlbum, uploadImage, uploadVideo } from '../api'
import { mediaUrl } from '../utils/media'

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

// 上传并发池：最多 3 个同时进行，超出入队；进度/计数/失败重试用
const UPLOAD_MAX_CONCURRENCY = 3
const uploadPool = reactive({ active: 0, queue: [] as File[] })
const uploadStats = reactive({ total: 0, done: 0, failed: 0 })
const failedFiles = ref<File[]>([])
const totalPct = computed(() =>
  uploadStats.total > 0 ? Math.round((uploadStats.done / uploadStats.total) * 100) : 0,
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

function onImageFilesPicked(event: Event): void {
  const input = event.target as HTMLInputElement
  const files = input.files ? Array.from(input.files) : []
  if (!files.length) return
  uploadStats.total += files.length
  uploadPool.queue.push(...files)
  pump()
  // 清空 input value 以允许再次选择同名文件
  input.value = ''
}

async function uploadOne(file: File): Promise<void> {
  try {
    const res = await uploadImage(file, { skipErrorToast: true })
    // 后端 R2 SHA256 去重命中时返回的 path 与已有记录相同，
    // 跳过 push 避免同一图集里出现重复图片（前台展示会重复）
    const dup = form.images.some((img) => img.path === res.data.path)
    if (!dup) {
      form.images.push({ ...res.data, sort: form.images.length + 1 })
    }
    uploadStats.done++
  } catch {
    uploadStats.failed++
    failedFiles.value.push(file)
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
      payload.images = form.images.map((img, i) => ({ ...img, sort: i + 1 }))
    }
    await saveAlbum(payload)
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
</style>
