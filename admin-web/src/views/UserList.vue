<template>
  <div class="page">
    <div class="page-header">
      <h2 class="page-title">用户管理</h2>
      <el-input v-model="keyword" placeholder="搜索用户名" style="width: 220px" clearable @keyup.enter="search" @clear="search" />
      <el-button type="primary" @click="search">搜索</el-button>
      <el-button type="success" @click="openCreate">新建用户</el-button>
    </div>
    <el-table :data="list" border v-loading="loading">
      <el-table-column prop="id" label="ID" width="60" />
      <el-table-column prop="username" label="用户名" />
      <el-table-column label="VIP 等级" width="90">
        <template #default="{ row }">
          <el-tag :type="row.vip_level > 0 ? 'warning' : 'info'">V{{ row.vip_level }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="vip_expire_at" label="VIP 到期">
        <template #default="{ row }">{{ row.vip_expire_at || '—' }}</template>
      </el-table-column>
      <el-table-column label="状态" width="80">
        <template #default="{ row }">
          <el-tag :type="row.status === 1 ? 'success' : 'danger'">{{ row.status === 1 ? '正常' : '禁用' }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="invite_code_used" label="邀请码" />
      <el-table-column prop="create_time" label="注册时间" width="170" />
      <el-table-column label="操作" width="160" fixed="right">
        <template #default="{ row }">
          <el-button size="small" @click="openEdit(row)">编辑</el-button>
          <el-button size="small" type="danger" @click="confirmDelete(row)">删除</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="page" :total="total" :page-size="20" layout="total, prev, pager, next" class="pager" @current-change="load" />

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="520px" @closed="resetForm">
      <el-form :model="form" label-width="90px" ref="formRef">
        <el-form-item label="用户名" :required="isCreate">
          <el-input v-model="form.username" :placeholder="isCreate ? '必填' : '留空则不改'" maxlength="50" />
        </el-form-item>
        <el-form-item label="密码" :required="isCreate">
          <el-input v-model="form.password" :placeholder="isCreate ? '至少 6 位' : '留空则不改密码'" show-password />
        </el-form-item>
        <el-form-item label="邮箱">
          <el-input v-model="form.email" placeholder="可选" />
        </el-form-item>
        <el-form-item label="VIP 等级">
          <el-select v-model="form.vip_level" style="width: 180px">
            <el-option label="V0 免费" :value="0" />
            <el-option label="V1" :value="1" />
            <el-option label="V2" :value="2" />
            <el-option label="V3" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item label="VIP 到期">
          <el-date-picker v-model="form.vip_expire_at" type="datetime" style="width: 240px" placeholder="不设则永久/原值" value-format="YYYY-MM-DD HH:mm:ss" />
          <el-button v-if="form.vip_expire_at" size="small" text type="primary" @click="form.vip_expire_at = null" style="margin-left: 8px">清除</el-button>
        </el-form-item>
        <el-form-item label="状态">
          <el-radio-group v-model="form.status">
            <el-radio :value="1">正常</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSubmit">保存</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import type { FormInstance } from 'element-plus'
import type { UserItem } from '../api'
import { deleteUser, getUsers, saveUser } from '../api'

const list = ref<UserItem[]>([])
const total = ref(0)
const page = ref(1)
const keyword = ref('')
const loading = ref(false)

interface UserForm {
  id: number
  username: string
  password: string
  email: string
  vip_level: number
  status: number
  vip_expire_at: string | null
}

const dialogVisible = ref(false)
const saving = ref(false)
const formRef = ref<FormInstance>()
const form = reactive<UserForm>({
  id: 0,
  username: '',
  password: '',
  email: '',
  vip_level: 0,
  status: 1,
  vip_expire_at: null,
})
const isCreate = computed(() => !form.id)
const dialogTitle = computed(() => (isCreate.value ? '新建用户' : `编辑用户 #${form.id}`))

async function load(): Promise<void> {
  loading.value = true
  try {
    const params: Record<string, unknown> = { page: page.value, size: 20 }
    if (keyword.value) params.keyword = keyword.value
    const { data } = await getUsers(params)
    list.value = data.items
    total.value = data.total
  } finally {
    loading.value = false
  }
}

function search(): void {
  page.value = 1
  void load()
}

function resetForm(): void {
  form.id = 0
  form.username = ''
  form.password = ''
  form.email = ''
  form.vip_level = 0
  form.status = 1
  form.vip_expire_at = null
  formRef.value?.clearValidate()
}

function openCreate(): void {
  resetForm()
  dialogVisible.value = true
}

function openEdit(row: UserItem): void {
  form.id = row.id
  form.username = ''
  form.password = ''
  form.email = row.email ?? ''
  form.vip_level = row.vip_level
  form.status = row.status
  form.vip_expire_at = row.vip_expire_at
  dialogVisible.value = true
}

async function handleSubmit(): Promise<void> {
  if (isCreate.value && !form.username.trim()) {
    ElMessage.warning('请输入用户名')
    return
  }
  if (isCreate.value && (form.password?.length ?? 0) < 6) {
    ElMessage.warning('密码至少 6 位')
    return
  }
  saving.value = true
  try {
    await saveUser({ ...form })
    ElMessage.success(isCreate.value ? '创建成功' : '更新成功')
    dialogVisible.value = false
    void load()
  } finally {
    saving.value = false
  }
}

async function confirmDelete(row: UserItem): Promise<void> {
  try {
    await ElMessageBox.confirm(
      `确认删除用户「${row.username}」？该用户的评论/收藏/浏览记录会一并删除，不可恢复。`,
      '删除确认',
      { type: 'warning', confirmButtonText: '确认删除', cancelButtonText: '取消' }
    )
  } catch {
    return
  }
  await deleteUser(row.id)
  ElMessage.success('删除成功')
  void load()
}

onMounted(load)
</script>

<style scoped>
.pager {
  margin-top: 16px;
  justify-content: flex-end;
}
</style>