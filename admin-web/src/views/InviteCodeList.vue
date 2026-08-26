<template>
  <div class="page">
    <div class="page-header">
      <h2 class="page-title">邀请码管理</h2>
      <div>
        <el-input-number v-model="genCount" :min="1" :max="500" />
        <el-button type="primary" class="ml8" :loading="generating" @click="handleGenerate">批量生成</el-button>
      </div>
    </div>
    <el-table :data="list" border>
      <el-table-column prop="id" label="ID" width="60" />
      <el-table-column prop="code" label="邀请码" />
      <el-table-column label="状态" width="90">
        <template #default="{ row }">
          <el-tag :type="row.status === 0 ? 'success' : row.status === 1 ? 'warning' : 'info'">
            {{ row.status === 0 ? '未用' : row.status === 1 ? '已用' : '禁用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="used_by" label="使用者" width="80">
        <template #default="{ row }">{{ row.used_by ?? '—' }}</template>
      </el-table-column>
      <el-table-column prop="used_at" label="使用时间" />
      <el-table-column prop="expire_at" label="过期时间">
        <template #default="{ row }">{{ row.expire_at || '永久' }}</template>
      </el-table-column>
      <el-table-column label="操作" width="100">
        <template #default="{ row }">
          <el-button size="small" @click="handleToggle(row)">
            {{ row.status === 2 ? '启用' : '禁用' }}
          </el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="page" :total="total" :page-size="20" layout="total, prev, pager, next" class="pager" @current-change="load" />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import type { InviteCode } from '../api'
import { generateInviteCodes, getInviteCodes, toggleInviteCode } from '../api'

const list = ref<InviteCode[]>([])
const total = ref(0)
const page = ref(1)
const genCount = ref(10)
const generating = ref(false)

async function load(): Promise<void> {
  const { data } = await getInviteCodes({ page: page.value, size: 20 })
  list.value = data.items
  total.value = data.total
}

async function handleGenerate(): Promise<void> {
  generating.value = true
  try {
    const { data } = await generateInviteCodes(genCount.value)
    ElMessage.success(`已生成 ${data.count} 个邀请码`)
    await load()
  } finally {
    generating.value = false
  }
}

async function handleToggle(row: InviteCode): Promise<void> {
  await toggleInviteCode(row.id)
  ElMessage.success('操作成功')
  await load()
}

onMounted(load)
</script>

<style scoped>
.ml8 {
  margin-left: 8px;
}
.pager {
  margin-top: 16px;
  justify-content: flex-end;
}
</style>
