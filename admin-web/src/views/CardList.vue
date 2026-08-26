<template>
  <div class="page">
    <div class="page-header">
      <h2 class="page-title">卡密管理</h2>
      <div>
        <el-select v-model="batchFilter" style="width: 200px" clearable placeholder="全部批次" @change="load">
          <el-option v-for="b in batches" :key="b.id" :label="b.name" :value="b.id" />
        </el-select>
        <el-select v-model="statusFilter" style="width: 120px" class="ml8" @change="load">
          <el-option label="全部" :value="''" />
          <el-option label="未用" :value="0" />
          <el-option label="已用" :value="1" />
          <el-option label="禁用" :value="2" />
        </el-select>
      </div>
    </div>
    <el-table :data="list" border>
      <el-table-column prop="id" label="ID" width="60" />
      <el-table-column prop="code" label="卡密" min-width="200" />
      <el-table-column label="等级" width="70">
        <template #default="{ row }"><el-tag type="warning">V{{ row.level }}</el-tag></template>
      </el-table-column>
      <el-table-column prop="duration_days" label="天数" width="70" />
      <el-table-column label="状态" width="80">
        <template #default="{ row }">
          <el-tag :type="row.status === 0 ? 'success' : row.status === 1 ? 'warning' : 'info'">
            {{ row.status === 0 ? '未用' : row.status === 1 ? '已用' : '禁用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="used_by" label="使用者" width="80">
        <template #default="{ row }">{{ row.used_by ?? '—' }}</template>
      </el-table-column>
      <el-table-column prop="used_at" label="使用时间" width="170">
        <template #default="{ row }">{{ row.used_at || '—' }}</template>
      </el-table-column>
      <el-table-column label="操作" width="100">
        <template #default="{ row }">
          <el-button size="small" @click="handleToggle(row)">{{ row.status === 2 ? '启用' : '禁用' }}</el-button>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination v-model:current-page="page" :total="total" :page-size="20" layout="total, prev, pager, next" class="pager" @current-change="load" />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import type { CardBatch, CardItem } from '../api'
import { getCardBatches, getCards, toggleCard } from '../api'

const list = ref<CardItem[]>([])
const batches = ref<CardBatch[]>([])
const total = ref(0)
const page = ref(1)
const batchFilter = ref<number | ''>(Number(useQuery().batch_id) || '')
const statusFilter = ref<number | ''>('')

function useQuery(): Record<string, string> {
  return Object.fromEntries(new URLSearchParams(window.location.search))
}

async function load(): Promise<void> {
  const params: Record<string, unknown> = { page: page.value, size: 20 }
  if (batchFilter.value !== '') params.batch_id = batchFilter.value
  if (statusFilter.value !== '') params.status = statusFilter.value
  const { data } = await getCards(params)
  list.value = data.items
  total.value = data.total
}

async function loadBatches(): Promise<void> {
  const { data } = await getCardBatches()
  batches.value = data
}

async function handleToggle(row: CardItem): Promise<void> {
  await toggleCard(row.id)
  ElMessage.success('操作成功')
  await load()
}

onMounted(() => {
  void loadBatches()
  void load()
})
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
