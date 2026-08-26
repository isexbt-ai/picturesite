<template>
  <div class="page">
    <h2 class="page-title">VIP 手动发放</h2>
    <el-card style="max-width: 480px">
      <el-form label-width="90px">
        <el-form-item label="用户名" required>
          <el-input v-model="form.username" placeholder="用户登录名" />
        </el-form-item>
        <el-form-item label="等级" required>
          <el-select v-model="form.level" style="width: 100%">
            <el-option label="V1" :value="1" />
            <el-option label="V2" :value="2" />
            <el-option label="V3" :value="3" />
          </el-select>
        </el-form-item>
        <el-form-item label="有效天数" required>
          <el-input-number v-model="form.days" :min="1" :max="3650" />
        </el-form-item>
        <el-form-item label="备注">
          <el-input v-model="form.remark" placeholder="可选" />
        </el-form-item>
        <el-button type="primary" :loading="loading" @click="handleGrant">发放 VIP</el-button>
      </el-form>
      <el-alert v-if="result" :title="`已发放：等级 V${result.new_level}，到期 ${result.vip_expire_at}`" type="success" class="mt16" />
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { grantVip } from '../api'

const loading = ref(false)
const form = reactive({ username: '', level: 1, days: 30, remark: '' })
const result = ref<{ new_level: number; vip_expire_at: string } | null>(null)

async function handleGrant(): Promise<void> {
  if (!form.username) {
    ElMessage.warning('请输入用户名')
    return
  }
  loading.value = true
  try {
    const { data } = await grantVip({ ...form })
    result.value = data
    ElMessage.success('发放成功')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.mt16 {
  margin-top: 16px;
}
</style>
