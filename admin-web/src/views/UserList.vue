<template>
  <div class="page">
    <div class="page-header">
      <h2 class="page-title">用户管理</h2>
      <el-input v-model="keyword" placeholder="搜索用户名" style="width: 220px" clearable @keyup.enter="search" @clear="search" />
      <el-button type="primary" @click="search">搜索</el-button>
    </div>
    <el-table :data="list" border>
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
    </el-table>
    <el-pagination v-model:current-page="page" :total="total" :page-size="20" layout="total, prev, pager, next" class="pager" @current-change="load" />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import type { UserItem } from '../api'
import { getUsers } from '../api'

const list = ref<UserItem[]>([])
const total = ref(0)
const page = ref(1)
const keyword = ref('')

async function load(): Promise<void> {
  const params: Record<string, unknown> = { page: page.value, size: 20 }
  if (keyword.value) params.keyword = keyword.value
  const { data } = await getUsers(params)
  list.value = data.items
  total.value = data.total
}

function search(): void {
  page.value = 1
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
