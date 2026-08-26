<template>
  <div class="page">
    <div class="page-header">
      <h2 class="page-title">内容管理</h2>
      <el-button type="primary" @click="router.push('/contents/create')">新建内容</el-button>
    </div>
    <div class="toolbar">
      <el-select v-model="filter.type" style="width: 120px" clearable placeholder="类型" @change="load">
        <el-option label="图集" value="album" />
        <el-option label="单图" value="single" />
        <el-option label="视频" value="video" />
      </el-select>
      <el-select v-model="filter.status" style="width: 120px" clearable placeholder="状态" @change="load">
        <el-option label="草稿" :value="0" />
        <el-option label="已发布" :value="1" />
        <el-option label="已下架" :value="2" />
      </el-select>
      <el-input v-model="filter.keyword" style="width: 200px" placeholder="标题搜索" clearable @keyup.enter="load" @clear="load" />
      <el-button type="primary" @click="load">搜索</el-button>
    </div>
    <el-table :data="list" border>
      <el-table-column prop="id" label="ID" width="60" />
      <el-table-column label="封面" width="70">
        <template #default="{ row }">
          <el-image v-if="row.cover" :src="mediaUrl(row.cover)" fit="cover" style="width: 44px; height: 58px; border-radius: 4px" />
          <span v-else>—</span>
        </template>
      </el-table-column>
      <el-table-column prop="title" label="标题" min-width="160" show-overflow-tooltip />
      <el-table-column label="类型" width="80">
        <template #default="{ row }">
          <el-tag :type="row.type === 'video' ? 'danger' : row.type === 'single' ? 'info' : ''">{{ typeLabel(row.type) }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="等级" width="70">
        <template #default="{ row }"><el-tag type="warning">L{{ row.level }}</el-tag></template>
      </el-table-column>
      <el-table-column prop="category.name" label="分类" width="100">
        <template #default="{ row }">{{ row.category?.name || '—' }}</template>
      </el-table-column>
      <el-table-column label="状态" width="80">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : row.status === 0 ? 'info' : 'danger'">{{ ['草稿', '已发布', '已下架'][row.status] }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="view_count" label="浏览" width="80" />
      <el-table-column label="操作" width="150">
        <template #default="{ row }">
          <el-button size="small" @click="router.push(`/contents/edit/${row.id}`)">编辑</el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="page" :total="total" :page-size="20" layout="total, prev, pager, next" class="pager" @current-change="load" />
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { AlbumItem } from '../api'
import { deleteAlbum, getAlbums } from '../api'
import { mediaUrl } from '../utils/media'

const router = useRouter()
const list = ref<AlbumItem[]>([])
const total = ref(0)
const page = ref(1)
const filter = reactive<{ type: string; status: number | ''; keyword: string }>({ type: '', status: '', keyword: '' })

function typeLabel(type: string): string {
  return type === 'album' ? '图集' : type === 'single' ? '单图' : '视频'
}

async function load(): Promise<void> {
  const params: Record<string, unknown> = { page: page.value, size: 20 }
  if (filter.type) params.type = filter.type
  if (filter.status !== '') params.status = filter.status
  if (filter.keyword) params.keyword = filter.keyword
  const { data } = await getAlbums(params)
  list.value = data.items
  total.value = data.total
}

async function handleDelete(row: AlbumItem): Promise<void> {
  await ElMessageBox.confirm(`确定删除内容「${row.title}」？关联图片/视频将一并删除。`, '警告', { type: 'warning' })
  await deleteAlbum(row.id)
  ElMessage.success('删除成功')
  await load()
}

onMounted(load)
</script>

<style scoped>
.pager {
  margin-top: 16px;
  justify-content: flex-end;
}
</style>
