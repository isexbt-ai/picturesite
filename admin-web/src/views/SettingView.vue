<template>
  <div class="page">
    <h2 class="page-title">系统设置</h2>
    <el-card style="max-width: 520px">
      <el-form label-width="120px">
        <el-form-item label="站点名称">
          <el-input v-model="form.site_name" />
        </el-form-item>
        <el-form-item label="开启评论">
          <el-switch v-model="form.comment_enabled" active-value="1" inactive-value="0" />
        </el-form-item>
        <el-form-item label="评论自动通过">
          <el-switch v-model="form.comment_auto_approve" active-value="1" inactive-value="0" />
        </el-form-item>
        <el-button type="primary" :loading="saving" @click="handleSave">保存设置</el-button>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { getSettings, saveSettings } from '../api'

const saving = ref(false)
const form = reactive<Record<string, string>>({ site_name: '图站', comment_enabled: '1', comment_auto_approve: '1' })

onMounted(async () => {
  const { data } = await getSettings()
  Object.assign(form, data)
})

async function handleSave(): Promise<void> {
  saving.value = true
  try {
    await saveSettings({ ...form })
    ElMessage.success('保存成功')
  } finally {
    saving.value = false
  }
}
</script>
