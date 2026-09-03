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
            <el-upload :show-file-list="false" :http-request="handleUploadCover" accept="image/*">
              <el-button type="primary" plain>{{ coverPreview ? '更换封面' : '上传封面' }}</el-button>
            </el-upload>
          </div>
        </el-form-item>

        <!-- 图片内容：图集/单图 -->
        <template v-if="form.type !== 'video'">
          <el-form-item label="图片">
            <el-upload :show-file-list="false" :http-request="handleUploadImage" accept="image/*" multiple>
              <el-button type="success" plain>上传图片</el-button>
            </el-upload>
          </el-form-item>
          <el-form-item v-if="form.images.length" label="图片列表">
            <div class="img-grid">
              <div v-for="(img, i) in form.images" :key="i" class="img-item">
                <el-image :src="mediaUrl(img.path)" fit="cover" class="img-preview" />
                <el-button size="small" type="danger" class="img-remove" @click="removeImage(i)">删除</el-button>
              </div>
            </div>
          </el-form-item>
        </template>

        <!-- 视频内容 -->
        <template v-else>
          <el-form-item label="视频文件">
            <el-upload :show-file-list="false" :http-request="handleUploadVideoFile" accept="video/mp4">
              <el-button type="success" plain>上传 MP4</el-button>
            </el-upload>
            <span v-if="form.video.path" class="video-info">已上传：{{ form.video.path }}</span>
          </el-form-item>
          <el-form-item label="视频封面">
            <el-upload :show-file-list="false" :http-request="handleUploadPoster" accept="image/*">
              <el-button plain>上传封面图</el-button>
            </el-upload>
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
import type { UploadRequestOptions } from 'element-plus'
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
  cover_webp: '',
  images: [] as ImageItem[],
  video: { path: '', poster: '', duration: 0, width: 0, height: 0, size: 0 },
})

async function handleUploadCover(options: UploadRequestOptions): Promise<void> {
  const { data } = await uploadImage(options.file)
  form.cover = data.path
  form.cover_thumb = data.thumb_path ?? ''
  form.cover_webp = data.webp_path ?? ''
  coverPreview.value = mediaUrl(data.path)
  ElMessage.success('封面上传成功')
}

async function handleUploadImage(options: UploadRequestOptions): Promise<void> {
  const { data } = await uploadImage(options.file)
  form.images.push({ ...data, sort: form.images.length + 1 })
  ElMessage.success('图片上传成功')
}

function removeImage(index: number): void {
  form.images.splice(index, 1)
}

async function handleUploadVideoFile(options: UploadRequestOptions): Promise<void> {
  const { data } = await uploadVideo(options.file)
  form.video.path = data.path
  form.video.size = data.size
  ElMessage.success('视频上传成功')
}

async function handleUploadPoster(options: UploadRequestOptions): Promise<void> {
  const { data } = await uploadImage(options.file)
  form.video.poster = data.path
  ElMessage.success('视频封面上传成功')
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
  form.cover_webp = data.cover_webp ?? ''
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
      cover_webp: form.cover_webp,
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
.img-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.img-item {
  position: relative;
  width: 120px;
}
.img-preview {
  width: 120px;
  height: 160px;
  border-radius: 6px;
}
.img-remove {
  position: absolute;
  top: 4px;
  right: 4px;
}
.video-info {
  margin-left: 12px;
  color: #909399;
  font-size: 13px;
  word-break: break-all;
}
</style>
