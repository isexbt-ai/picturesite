<template>
  <div class="page">
    <div class="page-header">
      <h2 class="page-title">评论审核</h2>
      <div>
        <el-select v-model="statusFilter" style="width: 140px" @change="load">
          <el-option label="全部" :value="''" />
          <el-option label="显示中" :value="1" />
          <el-option label="已隐藏" :value="0" />
        </el-select>
      </div>
    </div>
    <el-table :data="list" border>
      <el-table-column prop="id" label="ID" width="60" />
      <el-table-column prop="user.username" label="用户" width="120" />
      <el-table-column prop="album.title" label="内容" width="160">
        <template #default="{ row }">{{ row.album?.title || row.album_id }}</template>
      </el-table-column>
      <el-table-column prop="content" label="评论内容" show-overflow-tooltip />
      <el-table-column prop="create_time" label="时间" width="170" />
      <el-table-column label="状态" width="80">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'info'">{{ row.status === 1 ? '显示' : '隐藏' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="160">
        <template #default="{ row }">
          <el-button size="small" @click="handleToggle(row)">{{ row.status === 1 ? '隐藏' : '显示' }}</el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="page" :total="total" :page-size="20" layout="total, prev, pager, next" class="pager" @current-change="load" />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { CommentItem } from '../api'
import { deleteComment, getComments, toggleComment } from '../api'

const list = ref<CommentItem[]>([])
const total = ref(0)
const page = ref(1)
const statusFilter = ref<number | ''>('')

async function load(): Promise<void> {
  const params: Record<string, unknown> = { page: page.value, size: 20 }
  if (statusFilter.value !== '') params.status = statusFilter.value
  const { data } = await getComments(params)
  list.value = data.items
  total.value = data.total
}

async function handleToggle(row: CommentItem): Promise<void> {
  await toggleComment(row.id)
  ElMessage.success('操作成功')
  await load()
}

async function handleDelete(row: CommentItem): Promise<void> {
  await ElMessageBox.confirm('确定删除该评论？', '提示', { type: 'warning' })
  await deleteComment(row.id)
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
