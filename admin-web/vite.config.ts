import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// 后台前端：访问 /admin/，API 走 /admin-api 前缀（代理到后端）
export default defineConfig({
  plugins: [vue()],
  base: '/admin/',
  server: {
    port: 5173,
    proxy: {
      '/admin-api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
  build: {
    // 构建产物放入 public/admin（由 Nginx 托管）
    outDir: '../public/admin',
    emptyOutDir: true,
  },
})
