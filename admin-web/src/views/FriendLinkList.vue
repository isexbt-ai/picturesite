<template>
  <div class="page">
    <div class="page-header">
      <h2 class="page-title">友情链接</h2>
      <el-button type="primary" @click="openDialog()">新增友链</el-button>
    </div>
    <el-table :data="list" border>
      <el-table-column prop="id" label="ID" width="60" />
      <el-table-column prop="name" label="名称" width="180" />
      <el-table-column prop="url" label="URL" />
      <el-table-column prop="sort" label="排序" width="80" />
      <el-table-column prop="status" label="状态" width="100">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'info'" size="small">
            {{ row.status === 1 ? '启用' : '禁用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="操作" width="240">
        <template #default="{ row }">
          <el-button size="small" @click="openDialog(row)">编辑</el-button>
          <el-button size="small" @click="handleToggle(row)">
            {{ row.status === 1 ? '禁用' : '启用' }}
          </el-button>
          <el-button size="small" type="danger" @click="handleDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="dialogVisible" :title="form.id ? '编辑友链' : '新增友链'" width="480px">
      <el-form label-width="80px">
        <el-form-item label="名称"><el-input v-model="form.name" /></el-form-item>
        <el-form-item label="URL">
          <el-input v-model="form.url" placeholder="https://..." />
        </el-form-item>
        <el-form-item label="排序"><el-input-number v-model="form.sort" :min="0" /></el-form-item>
        <el-form-item label="状态">
          <el-switch
            v-model="form.status"
            :active-value="1"
            :inactive-value="0"
            active-text="启用"
            inactive-text="禁用"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FriendLink } from '../api'
import { deleteFriendLink, getFriendLinks, saveFriendLink, toggleFriendLink } from '../api'

const list = ref<FriendLink[]>([])
const dialogVisible = ref(false)
const saving = ref(false)
const form = reactive<Partial<FriendLink>>({ id: 0, name: '', url: '', sort: 0, status: 1 })

async function load(): Promise<void> {
  const { data } = await getFriendLinks()
  list.value = data
}

function openDialog(row?: FriendLink): void {
  Object.assign(
    form,
    row ? { ...row } : { id: 0, name: '', url: '', sort: 0, status: 1 },
  )
  dialogVisible.value = true
}

async function handleSave(): Promise<void> {
  saving.value = true
  try {
    await saveFriendLink({ ...form })
    ElMessage.success('保存成功')
    dialogVisible.value = false
    await load()
  } finally {
    saving.value = false
  }
}

async function handleToggle(row: FriendLink): Promise<void> {
  await toggleFriendLink(row.id)
  ElMessage.success('已切换状态')
  await load()
}

async function handleDelete(row: FriendLink): Promise<void> {
  await ElMessageBox.confirm(`确定删除友链「${row.name}」？`, '提示', { type: 'warning' })
  await deleteFriendLink(row.id)
  ElMessage.success('删除成功')
  await load()
}

onMounted(load)
</script>
