<template>
  <div class="page">
    <div class="page-header">
      <h2 class="page-title">卡密批次</h2>
      <el-button type="primary" @click="dialogVisible = true">创建批次</el-button>
    </div>
    <el-table :data="list" border>
      <el-table-column prop="id" label="ID" width="60" />
      <el-table-column prop="name" label="批次名称" />
      <el-table-column label="等级" width="80">
        <template #default="{ row }"><el-tag type="warning">V{{ row.level }}</el-tag></template>
      </el-table-column>
      <el-table-column prop="duration_days" label="天数" width="80" />
      <el-table-column prop="total" label="总数" width="80" />
      <el-table-column prop="used_count" label="已用" width="80" />
      <el-table-column label="操作" width="140">
        <template #default="{ row }">
          <el-button size="small" @click="viewCards(row)">查看卡密</el-button>
          <el-button size="small" type="success" @click="exportCards(row)">导出</el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog v-model="dialogVisible" title="创建卡密批次" width="420px">
      <el-form label-width="90px">
        <el-form-item label="批次名称"><el-input v-model="form.name" placeholder="如：5月 V2 月卡" /></el-form-item>
        <el-form-item label="等级">
          <el-select v-model="form.level" style="width: 100%">
            <el-option label="V1" :value="1" />
            <el-option label="V2" :value="2" />
            <el-option label="V3" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item label="有效天数"><el-input-number v-model="form.days" :min="1" :max="3650" /></el-form-item>
        <el-form-item label="生成数量"><el-input-number v-model="form.total" :min="1" :max="10000" /></el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="saving" @click="handleSave">生成</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import type { CardBatch } from '../api'
import { getCardBatches, saveCardBatch } from '../api'

const router = useRouter()
const list = ref<CardBatch[]>([])
const dialogVisible = ref(false)
const saving = ref(false)
const form = reactive({ name: '', level: 1, days: 30, total: 50 })

async function load(): Promise<void> {
  const { data } = await getCardBatches()
  list.value = data
}

async function handleSave(): Promise<void> {
  if (!form.name) {
    ElMessage.warning('请输入批次名称')
    return
  }
  saving.value = true
  try {
    await saveCardBatch({ ...form })
    ElMessage.success('批次创建成功')
    dialogVisible.value = false
    await load()
  } finally {
    saving.value = false
  }
}

function viewCards(row: CardBatch): void {
  router.push({ path: '/cards', query: { batch_id: String(row.id) } })
}

function exportCards(row: CardBatch): void {
  // 直接浏览器下载（后端返回纯文本）
  window.open(`/admin-api/card/export/batch_id/${row.id}`, '_blank')
}

onMounted(load)
</script>
