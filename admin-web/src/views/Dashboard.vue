<template>
  <div class="page">
    <h2 class="page-title">数据概览</h2>
    <el-row :gutter="16">
      <el-col :span="6" v-for="item in statCards" :key="item.label">
        <el-card class="stat-card">
          <div class="stat-label">{{ item.label }}</div>
          <div class="stat-value">{{ item.value }}</div>
        </el-card>
      </el-col>
    </el-row>
    <el-row :gutter="16" class="mt16">
      <el-col :span="12">
        <el-card>
          <template #header>内容类型分布</template>
          <el-descriptions :column="1" border>
            <el-descriptions-item label="图集">{{ stats?.album_type.album ?? 0 }}</el-descriptions-item>
            <el-descriptions-item label="单图">{{ stats?.album_type.single ?? 0 }}</el-descriptions-item>
            <el-descriptions-item label="视频">{{ stats?.album_type.video ?? 0 }}</el-descriptions-item>
          </el-descriptions>
        </el-card>
      </el-col>
      <el-col :span="12">
        <el-card>
          <template #header>运营数据</template>
          <el-descriptions :column="1" border>
            <el-descriptions-item label="注册用户">{{ stats?.user_total ?? 0 }}</el-descriptions-item>
            <el-descriptions-item label="VIP 用户">{{ stats?.vip_total ?? 0 }}</el-descriptions-item>
            <el-descriptions-item label="累计浏览量">{{ stats?.view_total ?? 0 }}</el-descriptions-item>
            <el-descriptions-item label="已用 / 未用卡密">{{ stats?.card_used ?? 0 }} / {{ stats?.card_unused ?? 0 }}</el-descriptions-item>
            <el-descriptions-item label="邀请码已用 / 总量">{{ stats?.invite_used ?? 0 }} / {{ stats?.invite_total ?? 0 }}</el-descriptions-item>
          </el-descriptions>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import type { Stats } from '../api'
import { getDashboard } from '../api'

const stats = ref<Stats | null>(null)

const statCards = computed(() => [
  { label: '内容总数', value: stats.value?.album_total ?? 0 },
  { label: '注册用户', value: stats.value?.user_total ?? 0 },
  { label: 'VIP 用户', value: stats.value?.vip_total ?? 0 },
  { label: '累计浏览', value: stats.value?.view_total ?? 0 },
])

onMounted(async () => {
  const { data } = await getDashboard()
  stats.value = data
})
</script>

<style scoped>
.stat-card {
  text-align: center;
}
.stat-label {
  color: #909399;
  font-size: 13px;
}
.stat-value {
  font-size: 28px;
  font-weight: 600;
  margin-top: 8px;
}
.mt16 {
  margin-top: 16px;
}
</style>
